<?php


declare( strict_types = 1 );


namespace JDWX\CLI;


use Exception;
use JDWX\App\Application;
use JDWX\App\InteractiveApplication;
use JDWX\Args\ArgumentException;
use JDWX\Args\Arguments;
use JDWX\Args\BadArgumentException;
use JDWX\Args\ExtraArgumentsException;
use JDWX\Args\ParsedString;
use JDWX\Args\StringParser;
use JDWX\Strict\OK;
use JDWX\Strict\TypeIs;
use Psr\Log\LoggerInterface;
use ReflectionClass;


class BaseInterpreter extends InteractiveApplication {


    public const string DEFAULT_COMMAND = '____OVERLOAD_ME____';

    public int $rc;

    protected bool $bContinue;

    /** @var array<string, string|AbstractCommand> */
    protected array $commands = [];

    /** @var array<string, string> */
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
    }


    protected static function commandToString( string|AbstractCommand $i_cmd ) : string {
        return is_string( $i_cmd ) ? $i_cmd : $i_cmd::class;
    }


    /** @return string[] */
    public function getHistory() : array {
        return $this->rHistory;
    }


    /**
     * We're going to peel out argument-related exceptions so we can
     * recover them and keep going.
     */
    public function handleArgumentException( Exception $i_ex ) : bool {
        if ( ! $i_ex instanceof ArgumentException ) {
            return false;
        }

        $r = Application::throwableToArray( $i_ex, false );
        $stMessage = $r[ 'message' ];
        unset( $r[ 'message' ] );

        if ( $i_ex instanceof BadArgumentException ) {
            $r[ 'value' ] = $i_ex->getValue();
        } elseif ( $i_ex instanceof ExtraArgumentsException ) {
            $r[ 'extra' ] = $i_ex->getArguments();
        }

        $this->error( $stMessage, $r );
        return true;
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
     * @param string $_stText Unused. Required by the readline completion
     *                        function signature.
     * @param int $i_nIndex How far into the text we are.
     * @return string[] The completion options.
     *
     * @noinspection PhpUnusedParameterInspection The _stText parameter
     * contains only the current word, which is not useful for our multi-word
     * command completion. The readlineInfo() contains better information
     * about the full entry so far, so we'll ignore the first parameter and
     * use that instead.
     */
    public function readlineCompletion( string $_stText, int $i_nIndex ) : array {
        # This prevents readline from ever looking at filenames as an autocomplete option.
        $this->readlineInfoSet( 'attempted_completion_over', 1 );
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
                if ( str_contains( $st, ' ' ) ) {
                    /**
                     * I literally just checked that.
                     * @phpstan-ignore argument.type
                     */
                    $st = substr( $st, 0, strpos( $st, ' ' ) );
                }
                $rWordMatches[] = $st;
            }
        }
        if ( $rCommands && 1 === count( $rMatches ) ) {
            echo "\n";
            $this->showHelp( $rCommands );
            $this->readlineRedisplay();
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
     * @param list<string>|null $i_rstCommands
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


    protected function activate() : void {
        $fn = function ( string $i_stText, int $i_nIndex ) : array {
            // @codeCoverageIgnoreStart Can't be tested non-interactively.
            return $this->readlineCompletion( $i_stText, $i_nIndex );
            // @codeCoverageIgnoreEnd
        };
        $this->readlineCompletionFunction( $fn );
    }


    /**
     * Do not call this method yourself. Doing so is unsupported. This method exists separately
     * from addCommandClass() only for historical reasons.
     */
    protected function addCommand( string  $i_stCommand, string|AbstractCommand $i_command,
                                   ?string $i_nstHelp = null,
                                   ?string $i_nstUsage = null ) : void {

        if ( self::DEFAULT_COMMAND === $i_stCommand ) {
            $stCommand = self::commandToString( $i_command );
            throw new \InvalidArgumentException( "No command name provided for command '{$stCommand}'" );
        }

        if ( ! is_string( $i_nstUsage ) ) {
            $i_nstUsage = $i_stCommand;
        }

        if ( ! $i_nstHelp ) {
            $i_nstHelp = 'No help available.';
        }

        if ( isset( $this->commands[ $i_stCommand ] ) ) {
            $stCommand = self::commandToString( $i_command );
            throw new \InvalidArgumentException( "Command '{$i_stCommand}' is already registered before {$stCommand}" );
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
        $cmd = $this->commandFromClassName( $i_stCommandClass );
        if ( $cmd instanceof AbstractCommand ) {
            $this->addCommandObject( $cmd );
            return;
        }
        throw new \InvalidArgumentException( "Failed to add command class: {$cmd}" );
    }


    /**
     * Auto-discover and register every concrete AbstractCommand subclass found
     * in $i_stDirectory. The directory must follow PSR-4 such that each file's
     * class autoloads based on its filename and the provided namespace.
     *
     * Files whose class is abstract, is a trait/interface, or doesn't extend
     * AbstractCommand are skipped silently — so it's safe to leave shared
     * base classes (e.g., CoreCommand) in the same directory.
     */
    protected function addCommandDirectory( string $i_stNamespace, string $i_stDirectory ) : void {
        $rFiles = OK::scandir( $i_stDirectory );
        $stNamespace = rtrim( $i_stNamespace, '\\' );
        foreach ( $rFiles as $stFile ) {
            if ( ! str_ends_with( $stFile, '.php' ) ) {
                continue;
            }
            $stClass = $stNamespace . '\\' . substr( $stFile, 0, -4 );
            $cmd = $this->commandFromClassName( $stClass );
            if ( $cmd instanceof AbstractCommand ) {
                $this->addCommandObject( $cmd );
            } else {
                $this->debug( "Skipped {$stFile}: {$cmd}" );
            }
        }
    }


    protected function addCommandObject( AbstractCommand $i_cmd ) : void {
        $this->addCommand( $i_cmd->getCommand(), $i_cmd, $i_cmd->getHelp(), $i_cmd->getUsage() );
        foreach ( $i_cmd->getAliases() as $stAlias ) {
            $this->addCommand( $stAlias, $i_cmd, $i_cmd->getHelp(), $i_cmd->getUsage() );
        }
    }


    /**
     * @param string $i_stClass The class name to instantiate a command from.
     * @return AbstractCommand|string The command on success or an error message on failure.
     */
    protected function commandFromClassName( string $i_stClass ) : AbstractCommand|string {
        if ( ! class_exists( $i_stClass ) ) {
            return "Class {$i_stClass} does not exist.";
        }
        $r = new ReflectionClass( $i_stClass );
        if ( $r->isAbstract() ) {
            return "Class {$i_stClass} is abstract.";
        }
        if ( $r->isInterface() ) {
            return "Class {$i_stClass} is an interface.";
        }
        if ( $r->isTrait() ) {
            return "Class {$i_stClass} is a trait.";
        }
        if ( ! $r->isSubclassOf( AbstractCommand::class ) ) {
            return "Class {$i_stClass} is not a command.";
        }
        $cmd = new $i_stClass( $this );
        assert( $cmd instanceof AbstractCommand );
        return $cmd;
    }


    /**
     * @param ParsedString $i_command
     * @return void
     *
     * This method handles converting a parsed string into a specific command
     * and its arguments. It then runs the command.
     */
    protected function handleCommandParsedString( ParsedString $i_command ) : void {

        $args = TypeIs::listString( $i_command->getSegments() );
        if ( 0 === count( $args ) ) {
            # This was a whole-line comment. (Truly empty lines were already handled.)
            return;
        }
        $st = implode( ' ', $args );
        $rMatches = CommandMatcher::match( $args, array_keys( $this->commands ) );
        $this->debug( 'matches = ' . json_encode( $rMatches, JSON_THROW_ON_ERROR ) );
        if ( 0 === count( $rMatches ) ) {
            $this->error( "Unknown command: {$st}" );
            return;
        }
        if ( 1 < count( $rMatches ) ) {
            $rMatches = CommandMatcher::winnow( $args, $rMatches );
            $this->debug( 'winnow = ' . json_encode( $rMatches, JSON_THROW_ON_ERROR ) );
            if ( 0 === count( $rMatches ) ) {
                // @codeCoverageIgnoreStart This *should* be unreachable.
                $this->error( "Invalid command: {$st}" );
                return;
                // @codeCoverageIgnoreEnd
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
        if ( [ '?' ] === $args ) {
            $this->showHelp( [ $stCommand ] );
            return;
        }
        $this->runCommand( $stCommand, TypeIs::listString( $args ), $st );
    }


    /** An input might be multiline. Sometimes readline does that if you paste multiline content. */
    protected function handleInput( string $stInput ) : void {
        foreach ( explode( "\n", $stInput ) as $line ) {
            $line = trim( $line );
            # echo "line = \"", $line, "\"\n";
            if ( '' === $line ) {
                return;
            }
            $this->readlineAddHistory( $line );
            $this->handleCommand( $line );
        }
    }


    /** Contains the basic read-eval-print-loop operation. */
    protected function main() : int {
        $this->activate();
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
     * @noinspection PhpParameterNameChangedDuringInheritanceInspection
     * @codeCoverageIgnore Can't be tested non-interactively.
     */
    protected function readLine( ?string $i_nstPrompt = null ) : false|string {
        return parent::readLine( $i_nstPrompt ?? $this->stPrompt );
    }


    /**
     * @param string $i_stCommand Which command to execute.
     * @param list<string> $i_args The arguments to the command.
     * @param string $i_stOriginal The full command line to be added to history on success.
     * @return void
     *
     * Finds the implementation of a given command and runs it with the given arguments.
     */
    protected function runCommand( string $i_stCommand, array $i_args, string $i_stOriginal ) : void {
        $method = $this->commands[ $i_stCommand ];
        $args = $this->newArguments( $i_args );
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
            if ( ! $this->handleArgumentException( $ex ) ) {
                throw $ex;
            }
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
