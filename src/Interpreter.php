<?php


namespace JDWX\CLI;


use Exception;
use JDWX\Args\ArgumentParser;
use JDWX\Args\Arguments;
use JDWX\Args\BadArgumentException;
use JDWX\CLI\Commands\CommandEcho;
use JDWX\CLI\Commands\CommandExit;
use JDWX\CLI\Commands\CommandExpr;
use JDWX\CLI\Commands\CommandHelp;
use Psr\Log\LoggerInterface;


class Interpreter extends Application {


    public int $rc;

    protected bool $bContinue;

    protected array $commands = [];

    protected array $help = [];

    protected string $stPrompt;


    public function __construct( string           $i_stPrompt = '> ', array|Arguments|null $i_argv = null,
                                 ?LoggerInterface $i_log = null ) {
        parent::__construct( $i_argv, $i_log );
        $this->stPrompt = $i_stPrompt;
        $this->help = [];
        $this->addCommandClass( CommandEcho::class );
        $this->addCommandClass( CommandExit::class );
        $this->addCommandClass( CommandExpr::class );
        $this->addCommandClass( CommandHelp::class );
        $fn = function ( string $i_stText, int $i_nIndex ) : array {
            return $this->readlineCompletion( $i_stText, $i_nIndex );
        };
        readline_completion_function( $fn );
    }


    public function askYN( string $i_stPrompt ) : bool {
        while ( true ) {
            $strYN = readline( $i_stPrompt );
            $bYN = ArgumentParser::parseBool( $strYN );
            if ( $bYN === true || $bYN === false ) return $bYN;
        }
    }


    /** @noinspection PhpUnusedParameterInspection The _stText parameter contains only the current word,
     * which is never useful in command line completion. */
    public function readlineCompletion( string $_stText, int $i_nIndex ) : array {
        $rlInfo = $this->readlineInfo();
        $fullInput = trim( substr( $rlInfo[ 'line_buffer' ], 0, $rlInfo[ 'end' ] ) );
        $rMatches = [];
        $rWordMatches = [];
        $rCommands = [];
        foreach ( $this->commands as $stCommand => $stMethod ) {
            if ( str_starts_with( $stCommand, $fullInput ) ) {
                if ( $stCommand === $fullInput ) {
                    $rCommands[] = $stCommand;
                }
                $st = substr( $stCommand, $i_nIndex );
                $rMatches[] = $st;
                if ( str_contains( $st, " " ) ) {
                    $st = substr( $st, 0, strpos( $st, " " ) );
                }
                $rWordMatches[] = $st;
            }
        }
        if ( 1 == count( $rMatches ) && $rCommands ) {
            echo "\n";
            $this->showHelp( $rCommands );
            readline_redisplay();
        }
        $rWordMatches = array_unique( $rWordMatches );
        if ( count( $rMatches ) > count( $rWordMatches ) ) {
            return $rWordMatches;
        }
        return $rMatches;
    }


    protected function readlineInfo() : array {
        # This prevents readline from ever looking at filenames as an autocomplete option.
        readline_info( 'attempted_completion_over', 1 );
        $rlInfo = readline_info();
        assert( is_array( $rlInfo ) );
        return $rlInfo;
    }


    public function setContinue( bool $i_bContinue ) : void {
        $this->bContinue = $i_bContinue;
    }


    public function showHelp( ?array $i_rstCommands = null ) : void {
        if ( is_null( $i_rstCommands ) ) {
            $i_rstCommands = array_keys( $this->commands );
        }
        $rHelp = [];
        foreach ( $i_rstCommands as $stCommand ) {
            foreach ( $this->help as $stUsage => $stHelp ) {
                if ( str_starts_with( $stUsage, $stCommand ) ) {
                    $rHelp[ $stUsage ] = $stHelp;
                }
            }
        }
        $keys = array_keys( $rHelp );
        sort( $keys );
        foreach ( $keys as $key ) {
            echo $key, "\n", str_pad( $rHelp[ $key ], 80, ' ', STR_PAD_LEFT ), "\n";
        }
    }


    protected function addCommand( string  $i_stCommand, string|Command $i_stMethod,
                                   ?string $i_nstHelp = null,
                                   ?string $i_nstUsage = null ) : void {
        if ( is_string( $i_nstUsage ) ) {
            if ( ! str_starts_with( $i_nstUsage, $i_stCommand ) ) {
                $i_nstUsage = trim( $i_stCommand . ' ' . $i_nstUsage );
            }
        } else {
            $i_nstUsage = $i_stCommand;
        }

        if ( ! $i_nstHelp ) {
            $i_nstHelp = 'No help available.';
        }
        $this->commands[ $i_stCommand ] = $i_stMethod;
        $this->help[ $i_nstUsage ] = $i_nstHelp;

    }


    protected function addCommandClass( string $i_stCommandClass ) : void {
        $cmd = new $i_stCommandClass( $this );
        assert( $cmd instanceof Command );
        $this->addCommand( $cmd->getCommand(), $cmd, $cmd->getHelp(), $cmd->getUsage() );
        foreach ( $cmd->getAliases() as $stAlias ) {
            $this->addCommand( $stAlias, $cmd, $cmd->getHelp(), $cmd->getUsage() );
        }
    }


    protected function handleCommand( string $st ) : void {

        $args = self::parseLine( $st );
        if ( ! is_array( $args ) ) {
            $this->logError( $args );
            return;
        }
        $argc = count( $args );
        if ( 0 == $argc ) return;
        $this->logDebug( "parsed line = " . json_encode( $args ) );

        $rMatches = $this->commands;
        foreach ( $args as $ii => $argInput ) {
            $rNewMatches = [];
            foreach ( $rMatches as $stCommand => $stMethod ) {
                $rCommand = preg_split( '/\s+/', $stCommand );
                if ( $ii >= count( $rCommand ) ) {
                    // echo $ii, ". ", $stCommand, " <=> ", $argInput, " (length)\n";
                    $rNewMatches[ $stCommand ] = $stMethod;
                    continue;
                }
                $argCommand = $rCommand[ $ii ];
                if ( str_starts_with( $argCommand, $argInput ) ) {
                    // echo $ii, ". ", $argCommand, " <=> ", $argInput, " (match)\n";
                    $rNewMatches[ $stCommand ] = $stMethod;
                    // continue; # not needed when echo below is commented out
                }
                // echo $ii, ". ", $argCommand, " <=> ", $argInput, " (XXX)\n";
            }
            if ( empty( $rNewMatches ) ) {
                echo 'Unknown command: ', $st, "\n";
                return;
            }
            $rMatches = $rNewMatches;
        }
        $this->logDebug( "Matches: " . json_encode( array_keys( $rMatches ) ) );

        # If we have more than one match, we can try to winnow it down by looking at which command(s)
        # match the most components of the input.
        if ( count( $rMatches ) > 1 ) {
            $rNewMatches = [];
            $uMaxMatchLen = 0;
            foreach ( $rMatches as $stCommand => $stMethod ) {
                $rCommand = preg_split( '/\s+/', $stCommand );
                if ( count ( $rCommand ) == $argc ) {
                    # An exact match should always win over a longer inexact match.
                    $uMatchLen = 1_000_000;
                } else {
                    # Otherwise, the more components of the command that match the input, the better.
                    $uMatchLen = min( count( $rCommand ), $argc );
                }
                if ( $uMatchLen < $uMaxMatchLen ) continue;
                if ( $uMatchLen > $uMaxMatchLen ) {
                    $uMaxMatchLen = $uMatchLen;
                    $rNewMatches = [];
                }
                $rNewMatches[ $stCommand ] = $stMethod;
                // echo "Match: ", $stCommand, " (", $uMatchLen, ")\n";
            }
            $this->logDebug( "Winnowed matches: " . json_encode( array_keys( $rNewMatches ) ) );
            if ( count( $rNewMatches ) === 1 ) {
                # This handles the case where two commands match but one is objectively longer.
                # E.g., if you have commands "go" and "go hard" and you enter "go hard" we should
                # match "go hard" but not "go".
                $rMatches = $rNewMatches;
            } elseif ( count( $rNewMatches ) > 1 ) {
                # This handles the case where what you've entered matches two or more commands
                # to an equal amount. E.g., if you have commands "go fish" and "go hard" and you enter
                # "go" we show you your options.
                $this->logWarning( "Ambiguous command: {$st}" );
                $this->logDebug( "Matches: " . json_encode( array_keys( $rNewMatches ) ) );
                $this->showHelp( array_keys( $rNewMatches ) );
                return;
            } else {
                # This handles the case where what you've entered doesn't match any commands.
                # This shouldn't be reachable anymore, but it's hard to be sure, so we'll watch
                # for this message.
                $this->logError( "Command winnowing failed: {$st}" );
                return;
            }
        }
        assert( 1 === count( $rMatches ) );

        $match = array_key_first( $rMatches );
        $method = array_shift( $rMatches );
        $rCommand = explode( ' ', $match );
        // echo "Matched: ", $match, " (", count( $x ), " with ", $st, " (", $argc, ")\n";
        if ( count( $rCommand ) > $argc ) {
            $this->showHelp( [ $match ] );
            return;
        }
        $args = array_slice( $args, count( $rCommand ) );

        try {
            if ( $method instanceof Command ) {
                if ( 1 == count( $args ) && $args[ 0 ] == '?' ) {
                    $this->showHelp( [ $match ] );
                    return;
                }
                $args = new Arguments( $args );
                $method->runOuter( $args );
            } elseif ( is_string( $method ) ) {
                $this->$method( $this->newArguments( $args ) );
            }
        } catch ( BadArgumentException $ex ) {
            echo 'ERROR: ', $ex->getMessage(), "\n";
        } catch ( Exception $ex ) {
            echo 'EXCEPTION: ', get_class( $ex ),
            "\nCode: ", $ex->getCode(),
            "\nMessage: ", $ex->getMessage(),
            "\n";
            echo $ex->getTraceAsString(), "\n";
        }

    }


    /** An input might be multiline. Sometimes readline does that if you paste multiline content. */
    protected function handleInput( string $stInput ) : void {
        foreach ( explode( "\n", $stInput ) as $line ) {
            $line = trim( $line );
            # echo "line = \"", $line, "\"\n";
            if ( $line == "" ) {
                return;
            }
            readline_add_history( $line );
            $this->handleCommand( $line );
        }
    }


    protected function main() : int {
        $this->rc = 0;
        $this->bContinue = true;
        while ( $this->bContinue ) {
            $str = $this->readLine();
            if ( false === $str ) {
                echo "\n";
                break;
            }
            # echo "input = \"", $str, "\"\n";
            $this->handleInput( $str );
        }
        return $this->rc;
    }


    public static function parseQuote( string $i_st, string $i_stQuoteCharacter ) : array|string {
        $stOut = "";
        $stRest = $i_st;
        while ( true ) {
            $iSpan = strpos( $stRest, $i_stQuoteCharacter );
            if ( false === $iSpan ) {
                return "Unmatched {$i_stQuoteCharacter}.";
            }
            if ( $iSpan > 0 && substr( $stRest, $iSpan - 1, 1 ) === "\\" ) {
                $stOut .= substr( $stRest, 0, $iSpan - 1 ) . $i_stQuoteCharacter;
                $stRest = substr( $stRest, $iSpan + 1 );
                continue;
            }
            $stOut .= substr( $stRest, 0, $iSpan );
            $stRest = substr( $stRest, $iSpan + 1 );
            return [ $stOut, $stRest ];
        }
    }


    public static function parseLine( string $i_stLine ) : array|string {
        $st = trim( preg_replace( "/\s\s+/", " ", $i_stLine ) );
        $rOut = [];
        $stSpan = "";
        while ( $st !== "" ) {
            $iSpan = strcspn( $st, " \"'#`" );
            $stSpan .= substr( $st, 0, $iSpan );
            $ch = substr( $st, $iSpan, 1 );
            $stRest = substr( $st, $iSpan + 1 );
            // echo $stSpan, "|", $ch, "|", $stRest, "\n";
            if ( ' ' === $ch || '' === $ch ) {
                if ( $stSpan ) {
                    $rOut[] = $stSpan;
                    $stSpan = "";
                }
            } elseif ( '"' == $ch ) {
                $r = self::parseQuote( $stRest, '"' );
                if ( is_string( $r ) ) {
                    return $r;
                }
                $stSpan .= $r[ 0 ];
                $stRest = $r[ 1 ];
            } elseif ( "'" == $ch ) {
                $r = self::parseQuote( $stRest, '\'' );
                if ( is_string( $r ) ) {
                    return $r;
                }
                $stSpan .= $r[ 0 ];
                $stRest = $r[ 1 ];
            } elseif ( "`" === $ch ) {
                $r = self::parseQuote( $stRest, '`' );
                if ( is_string( $r ) ) {
                    return $r;
                }
                $stSpan .= $r[ 0 ];
                $stRest = $r[ 1 ];
            }
            $st = $stRest;
        }
        if ( $stSpan ) {
            $rOut[] = $stSpan;
        }
        return $rOut;

    }


    protected function readLine( ?string $i_nstPrompt = null ) : bool|string {
        if ( is_null( $i_nstPrompt ) ) {
            $i_nstPrompt = $this->stPrompt;
        }
        return readline( $i_nstPrompt );
    }


}
