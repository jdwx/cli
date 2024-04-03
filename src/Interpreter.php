<?php


namespace JDWX\CLI;


use Exception;
use JDWX\Args\ArgumentParser;
use JDWX\Args\Arguments;
use JDWX\Args\BadArgumentException;
use JDWX\CLI\Commands\CommandEcho;
use JDWX\CLI\Commands\CommandExit;
use JDWX\CLI\Commands\CommandHelp;
use Psr\Log\LoggerInterface;


class Interpreter extends Application {


    public int $rc;
    protected bool $bContinue;
    protected array $commands = [];
    protected array $help = [];
    protected string $stPrompt;


    public function __construct( string $i_stPrompt = '> ', array|Arguments|null $i_argv = null,
                                ?LoggerInterface $i_log = null ) {
        parent::__construct( $i_argv, $i_log );
        $this->stPrompt = $i_stPrompt;
        $this->help = [];
        $this->addCommandClass( CommandEcho::class );
        $this->addCommandClass( CommandExit::class );
        $this->addCommandClass( CommandHelp::class );
        $fn = function( string $i_stText, int $i_nIndex ) : array {
            return $this->readlineCompletion( $i_stText, $i_nIndex );
        };
        readline_completion_function( $fn );
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


    public function askYN( string $i_stPrompt ) : bool {
        while ( true ) {
            $strYN = readline( $i_stPrompt );
            $bYN = ArgumentParser::parseBool( $strYN );
            if ( $bYN === true || $bYN === false ) return $bYN;
        }
    }


    /** @noinspection PhpUnusedParameterInspection */
    public function readlineCompletion( string $i_stText, int $i_nIndex ) : array {
        $rlInfo = readline_info();
        assert( is_array( $rlInfo ) );
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


    public function main() : int {
        $this->rc = 0;
        $this->bContinue = true;
        while ( $this->bContinue ) {
            $str = $this->readLine( $this->stPrompt );
            if ( false === $str ) {
                echo "\n";
                break;
            }
            # echo "input = \"", $str, "\"\n";
            $this->handleInput( $str );
        }
        return $this->rc;
    }


    protected function handleCommand( string $st ) : void {

        $st = trim( preg_replace( "/[ \t][ \t]+/", " ", $st ) );
        if ( str_starts_with( $st, '#' ) ) return;
        $args = explode( ' ', $st );
        $argc = count( $args );

        $rMatches = $this->commands;
        foreach ( $args as $ii => $argInput ) {
            $rNewMatches = [];
            foreach ( $rMatches as $stCommand => $stMethod ) {
                $x = explode( ' ', $stCommand );
                if ( $ii >= count( $x ) ) {
                    // echo $ii, ". ", $stCommand, " <=> ", $argInput, " (length)\n";
                    $rNewMatches[ $stCommand ] = $stMethod;
                    continue;
                }
                $argCommand = $x[ $ii ];
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

        if ( count( $rMatches ) > 1 ) {
            $rNewMatches = [];
            $uMaxMatchLen = 0;
            foreach ( $rMatches as $stCommand => $stMethod ) {
                if ( str_starts_with( $st, $stCommand ) ) {
                    $uMatchLen = strlen( $stCommand );
                    if ( $uMatchLen < $uMaxMatchLen ) continue;
                    if ( $uMatchLen > $uMaxMatchLen ) {
                        $uMaxMatchLen = $uMatchLen;
                        $rNewMatches = [];
                    }
                    $rNewMatches[ $stCommand ] = $stMethod;
                }
            }
            if ( count( $rNewMatches ) === 1 ) {
                $rMatches = $rNewMatches;
            } else {
                echo 'Ambiguous command: ', $st, " (", count( $rNewMatches ), ")\n";
                echo "Matches: ", join( " ", array_keys( $rNewMatches ) ), "\n";
                $this->showHelp( array_keys( $rNewMatches ) );
                return;
            }
        }
        assert( 1 === count( $rMatches ) );

        $match = array_key_first( $rMatches );
        $method = array_shift( $rMatches );
        $x = explode( ' ', $match );
        // echo "Matched: ", $match, " (", count( $x ), " with ", $st, " (", $argc, ")\n";
        if ( count( $x ) > $argc ) {
            $this->showHelp( [ $match ] );
            return;
        }
        $args = array_slice( $args, count( $x ) );

        try {
            if ( $method instanceof Command ) {
                if ( 1 == count( $args ) && $args[ 0 ] == '?' ) {
                    $this->showHelp( [ $match ] );
                    return;
                }
                $args = new Arguments( $args );
                $method->run( $args );
            } elseif ( is_string( $method ) ) {
                $this->$method( $args );
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


    protected function readLine() : bool|string {
        return readline( $this->stPrompt );
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


}
