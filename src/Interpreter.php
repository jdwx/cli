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
use JDWX\CLI\Commands\CommandSet;
use Psr\Log\LoggerInterface;


class Interpreter extends Application {


    public int $rc;

    protected bool $bContinue;

    protected array $commands = [];

    protected array $help = [];

    protected string $stPrompt;

    /** @var array<string, string> */
    protected array $rVariables = [];


    public function __construct( string           $i_stPrompt = '> ', array|Arguments|null $i_argv = null,
                                 ?LoggerInterface $i_log = null ) {
        parent::__construct( $i_argv, $i_log );
        $this->stPrompt = $i_stPrompt;
        $this->help = [];
        $this->addCommandClass( CommandEcho::class );
        $this->addCommandClass( CommandExit::class );
        $this->addCommandClass( CommandExpr::class );
        $this->addCommandClass( CommandHelp::class );
        $this->addCommandClass( CommandSet::class );
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


    public function setVariable( string $i_stName, string $i_stValue ) : void {
        $this->rVariables[ $i_stName ] = $i_stValue;
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


    /**
     * Do not call this method yourself. Doing so is unsupported. This method exists separately
     * from addCommandClass() only for historical reasons.
     */
    protected function addCommand( string  $i_stCommand, string|AbstractCommand $i_command,
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
        $this->commands[ $i_stCommand ] = $i_command;
        $this->help[ $i_nstUsage ] = $i_nstHelp;

    }


    protected function addCommandClass( string $i_stCommandClass ) : void {
        $cmd = new $i_stCommandClass( $this );
        assert( $cmd instanceof AbstractCommand );
        $this->addCommand( $cmd->getCommand(), $cmd, $cmd->getHelp(), $cmd->getUsage() );
        foreach ( $cmd->getAliases() as $stAlias ) {
            $this->addCommand( $stAlias, $cmd, $cmd->getHelp(), $cmd->getUsage() );
        }
    }


    public function handleCommand( string $st ) : void {

        $rInput = LineParser::parseLine( $st );
        if ( ! $rInput instanceof ParsedLine ) {
            $this->logError( $rInput );
            return;
        }

        $bst = $rInput->substVariables( $this->rVariables );
        if ( is_string( $bst ) ) {
            $this->logError( $bst );
            return;
        }
        $rInput->substEscapeSequences();
        $rInput->substBackQuotes( $this );

        $args = $rInput->getSegments();

        $rMatches = CommandMatcher::match( $args, array_keys( $this->commands ) );
        $this->logDebug( "matches = " . json_encode( $rMatches ) );
        if ( 0 == count( $rMatches ) ) {
            $this->logError( "Unknown command: {$st}" );
            return;
        }
        if ( 1 < count( $rMatches ) ) {
            $rMatches = CommandMatcher::winnow( $args, $rMatches );
            $this->logDebug( "winnow = " . json_encode( $rMatches ) );
            if ( 0 == count( $rMatches ) ) {
                $this->logError( "Invalid command: {$st}" );
                return;
            }
            if ( 1 < count( $rMatches ) ) {
                $this->logWarning( "Ambiguous command: {$st}" );
                $this->showHelp( $rMatches );
                return;
            }
        }
        $stCommand = array_shift( $rMatches );
        $method = $this->commands[ $stCommand ];
        $args = $rInput->getSegments();
        $args = array_slice( $args, count( explode( ' ', $stCommand ) ) );
        if ( [ "?" ] == $args ) {
            $this->showHelp( [ $stCommand ] );
            return;
        }
        $args = $this->newArguments( $args );
        try {
            if ( $method instanceof AbstractCommand ) {
                $method->runOuter( $args );
                return;
            }
            # This exists only for historical reasons. It cannot be deprecated, yet. But this should never happen
            # in new code.
            if ( method_exists( $this, $method ) ) {
                $this->$method( $args );
            }
        } catch ( BadArgumentException $ex ) {
            $this->logError( $ex->getMessage() );
        } catch ( Exception $ex ) {
            $this->logError( 'EXCEPTION: ' . get_class( $ex ) . ': ' . $ex->getMessage() );
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


    protected function readLine( ?string $i_nstPrompt = null ) : bool|string {
        if ( is_null( $i_nstPrompt ) ) {
            $i_nstPrompt = $this->stPrompt;
        }
        return readline( $i_nstPrompt );
    }


}
