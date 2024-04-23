<?php


declare( strict_types = 1 );


namespace JDWX\CLI;


use Exception;
use JDWX\App\Application;
use JDWX\Args\ArgumentParser;
use JDWX\Args\Arguments;
use JDWX\Args\ParsedString;
use JDWX\Args\StringParser;
use Psr\Log\LoggerInterface;


class BaseInterpreter extends Application {


    public int $rc;

    protected bool $bContinue;

    protected array $commands = [];

    protected array $help = [];

    protected string $stPrompt;

    /** @var string[]
     * We can't use readline_list_history() because it doesn't work on all
     * systems, so we maintain our own history instead. This also allows
     * us to manipulate the history in ways that readline doesn't support.
     */
    protected array $rHistory = [];

    protected int $uHistoryLength = 100;


    public function __construct( string           $i_stPrompt = '> ', array|Arguments|null $i_argv = null,
                                 ?LoggerInterface $i_log = null ) {
        parent::__construct( $i_argv, $i_log );
        $this->stPrompt = $i_stPrompt;
        $this->activate();
    }


    public function activate() : void {
        $fn = function ( string $i_stText, int $i_nIndex ) : array {
            return $this->readlineCompletion( $i_stText, $i_nIndex );
        };
        readline_completion_function( $fn );
    }


    /**
     * @param string $i_stPrompt
     * @return bool
     *
     * Ask a yes/no question.  Returns true for yes, false for no.  If the user
     * enters something that can't be interpreted as "yes" or "no", the question
     * is repeated. See ArgumentParser::parseBool() for a list of recognized
     * values.
     */
    public function askYN( string $i_stPrompt ) : bool {
        while ( true ) {
            $strYN = $this->readLine( $i_stPrompt );
            $bYN = ArgumentParser::parseBool( $strYN );
            if ( $bYN === true || $bYN === false ) return $bYN;
        }
    }


    /** @return string[] */
    public function getHistory() : array {
        return $this->rHistory;
    }


    /**
     * @param string $st
     * @return void
     *
     * Performs the basic steps for parsing a command line into a ParsedString
     * object and then passing that on to handleCommandParsedString().  This
     * command is public so that the application can feed in commands that
     * were not entered interactively if needed.
     */
    public function handleCommand( string $st ) : void {

        if ( str_starts_with( $st, '!' ) ) {
            $st = substr( $st, 1 );
            $r = array_reverse( $this->rHistory );
            foreach ( $r as $line ) {
                if ( str_starts_with( $line, $st ) ) {
                    $this->handleCommand( $line );
                    return;
                }
            }
            $this->error( "No match in history: {$st}" );
        }

        $parsedString = StringParser::parseString( $st );
        if ( ! $parsedString instanceof ParsedString ) {
            $this->error( $parsedString );
            return;
        }

        if ( ! $this->subst( $parsedString ) ) {
            return;
        }
        $this->handleCommandParsedString( $parsedString );
    }


    /**
     * @noinspection PhpUnusedParameterInspection The _stText parameter contains only the current word,
     * which is not useful for our multi-word command completion. The readlineInfo() contains better
     * information about the full entry so far, so we'll ignore the first parameter and use that instead.
     */
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


    public function setContinue( bool $i_bContinue ) : void {
        $this->bContinue = $i_bContinue;
    }


    /**
     * @param array|null $i_rstCommands
     * @return void
     *
     * Show help for the given commands. If no commands are given, show help for all commands.
     */
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
        if ( ! is_string( $i_nstUsage ) ) {
            $i_nstUsage = $i_stCommand;
        }

        if ( ! $i_nstHelp ) {
            $i_nstHelp = 'No help available.';
        }
        $this->commands[ $i_stCommand ] = $i_command;
        $this->help[ $i_nstUsage ] = $i_nstHelp;

    }


    /**
     * @param class-string<AbstractCommand> $i_stCommandClass
     * @return void
     *
     * Add a command to the interpreter. Usually called from the constructor
     * of a subclass of BaseInterpreter to add the commands that the
     * interpreter will understand.  The $i_stCommandClass argument is
     * easiest to provide in the form CommandClassName::class.
     */
    protected function addCommandClass( string $i_stCommandClass ) : void {
        $cmd = new $i_stCommandClass( $this );
        assert( $cmd instanceof AbstractCommand );
        $this->addCommand( $cmd->getCommand(), $cmd, $cmd->getHelp(), $cmd->getUsage() );
        foreach ( $cmd->getAliases() as $stAlias ) {
            $this->addCommand( $stAlias, $cmd, $cmd->getHelp(), $cmd->getUsage() );
        }
    }


    /**
     * @param ParsedString $i_command
     * @return void
     *
     * This method handles converting a parsed string into a specific command
     * and its arguments. It then runs the command.
     */
    protected function handleCommandParsedString( ParsedString $i_command ) : void {

        $args = $i_command->getSegments();
        if ( 0 == count( $args ) ) {
            # This was a whole-line comment. (Truly empty lines were already handled.)
            return;
        }
        $st = implode( ' ', $args );
        $rMatches = CommandMatcher::match( $args, array_keys( $this->commands ) );
        $this->debug( "matches = " . json_encode( $rMatches ) );
        if ( 0 == count( $rMatches ) ) {
            $this->error( "Unknown command: {$st}" );
            return;
        }
        if ( 1 < count( $rMatches ) ) {
            $rMatches = CommandMatcher::winnow( $args, $rMatches );
            $this->debug( "winnow = " . json_encode( $rMatches ) );
            if ( 0 == count( $rMatches ) ) {
                $this->error( "Invalid command: {$st}" );
                return;
            }
            if ( 1 < count( $rMatches ) ) {
                $this->warning( "Ambiguous command: {$st}" );
                $this->showHelp( $rMatches );
                return;
            }
        }
        $stCommand = array_shift( $rMatches );
        $uCommandLength = count( explode( ' ', $stCommand ) );
        $args = array_slice( $args, $uCommandLength );
        $st = $stCommand . ' ' . $i_command->getOriginal( $uCommandLength );
        if ( [ "?" ] == $args ) {
            $this->showHelp( [ $stCommand ] );
            return;
        }
        $this->runCommand( $stCommand, $args, $st );
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


    /** Contains the basic read-eval-print-loop operation. */
    protected function main() : int {
        $this->rc = 0;

        # We set this to true here because some applications might want to
        # call this multiple times, e.g., for "escape to shell" type
        # functionality from within another part of the application.
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


    /**
     * We encapsulate readline stuff to the greatest extent possible because
     * it is very hard to test.  It's easier to mock with attachment points
     * like this one.
     */
    protected function readLine( ?string $i_nstPrompt = null ) : bool|string {
        if ( is_null( $i_nstPrompt ) ) {
            $i_nstPrompt = $this->stPrompt;
        }
        return readline( $i_nstPrompt );
    }


    protected function readlineInfo() : array {
        # This prevents readline from ever looking at filenames as an autocomplete option.
        readline_info( 'attempted_completion_over', 1 );
        $rlInfo = readline_info();
        assert( is_array( $rlInfo ) );
        return $rlInfo;
    }


    /**
     * @param string $i_stCommand Which command to execute.
     * @param array $args The arguments to the command.
     * @param string $i_stOriginal The full command line to be added to history on success.
     * @return void
     *
     * Finds the implementation of a given command and runs it with the given arguments.
     */
    protected function runCommand( string $i_stCommand, array $args, string $i_stOriginal ) : void {
        $method = $this->commands[ $i_stCommand ];
        $args = $this->newArguments( $args );
        try {
            $bHistory = true;
            if ( $method instanceof AbstractCommand ) {
                $method->runOuter( $args );
                $bHistory = $method::HISTORY;
            } elseif ( method_exists( $this, $method ) ) {
                $this->$method( $args );
            } else {
                $this->error( "No implementation for command: {$i_stCommand}" );
                return;
            }
            if ( $bHistory ) {
                $this->rHistory[] = $i_stOriginal;
                $this->rHistory = array_slice( $this->rHistory, -$this->uHistoryLength, preserve_keys: true );
            }
        } catch ( Exception $ex ) {
            $this->error( 'EXCEPTION: ' . get_class( $ex ) . ': ' . $ex->getMessage() );
            echo $ex->getTraceAsString(), "\n";
        }

    }


    /** @return bool Returns true if we should continue processing this input, otherwise false.
     *
     * Perform any desired substitutions. In the base implementation, this is just turning
     * backquoted text into nested commands.  The Interpreter class adds environment variable
     * substitution.
     */
    protected function subst( ParsedString $i_rInput ) : bool {
        $i_rInput->substBackQuotes( function ( string $i_st ) {
            ob_start();
            $this->handleCommand( $i_st );
            return ob_get_clean();
        } );
        return true;
    }


}
