<?php


declare( strict_types = 1 );


namespace JDWX\CLI;


use Exception;
use JDWX\Args\Arguments;
use JDWX\Args\Exceptions\ArgumentException;
use LogicException;
use Psr\Log\LoggerInterface;
use Psr\Log\LoggerTrait;
use Stringable;


/**
 * This class is the base class for all commands.  It provides the basic
 * structure for a command, including the command name, aliases, help text,
 * usage text, and options.  It also provides a method for running the command
 * and supports checking options.
 *
 * If you need special handling of arguments (i.e., with a subclass of
 * Arguments), you should create an abstract subclass of this with its own abstract
 * run() method with the proper signature.  If not, you can use the Command class
 * instead.
 */
abstract class AbstractCommand implements LoggerInterface {


    use LoggerTrait;


    protected const string COMMAND = BaseInterpreter::DEFAULT_COMMAND;

    /** @var list<string>|string|null */
    protected const array|string|null ALIASES = [];

    protected const string|null       HELP    = null;

    protected const string|null       USAGE   = null;

    /** @var array<string, string|bool> */
    protected const array             OPTIONS = [
        /** If options are used, they are of the form "key" => "default_value". */
    ];

    /**
     * If true, the command is added to history when run. This is almost always
     * desirable unless the command manipulates the history itself.
     */
    public const bool HISTORY = true;


    /** @var array<string, mixed>|null */
    private ?array $nrOptions = null;


    private ?string $nstRenamedCommand = null;


    public function __construct( private readonly Interpreter $cli ) {}


    public function askYN( string $i_stPrompt ) : bool {
        return $this->cli()->askYN( $i_stPrompt );
    }


    /**
     * @return list<string> Aliases for this command
     * @suppress PhanTypeMismatchReturn Phan doesn't know the type might change
     * in subclasses.
     */
    public function getAliases() : array {
        if ( is_array( static::ALIASES ) ) {
            return static::ALIASES;
        }
        if ( is_null( static::ALIASES ) ) {
            return [];
        }
        if ( is_string( static::ALIASES ) ) {
            return [ static::ALIASES ];
        }
        // @codeCoverageIgnoreStart This is unreachable.
        throw new LogicException( 'ALIASES must be array, string, or null.' );
        // @codeCoverageIgnoreEnd
    }


    public function getCommand() : string {
        return $this->nstRenamedCommand ?? static::COMMAND;
    }


    public function getHelp() : ?string {
        return static::HELP;
    }


    public function getUsage() : ?string {
        # USAGE is written in terms of the compile-time command name, so peel
        # that off (if present) and prepend the current name, which may have
        # been changed by rename().
        $stUsage = static::USAGE ?? '';
        if ( static::COMMAND === $stUsage ) {
            $stUsage = '';
        } elseif ( str_starts_with( $stUsage, static::COMMAND . ' ' ) ) {
            $stUsage = substr( $stUsage, strlen( static::COMMAND ) + 1 );
        }
        return trim( $this->getCommand() . ' ' . $stUsage );
    }


    public function log( mixed $level, string|Stringable $message, array $context = [] ) : void {
        $this->cli()->log( $level, $message, $context );
    }


    public function rename( string $i_stNewCommand ) : void {
        $this->nstRenamedCommand = $i_stNewCommand;
    }


    /**
     * @suppress PhanUndeclaredMethod The run() method must exist, but we can't specify the
     * type of its argument. Users will frequently want to use a specific subclass of Arguments.
     */
    public function runOuter( Arguments $args ) : void {
        assert( method_exists( $this, 'run' ), 'Command ' . $this->getCommand() . ' has no run method.' );
        try {
            $this->run( $args );
        } catch ( Exception $ex ) {
            if ( $this->handleException( $ex ) ) {
                return;
            }
            throw $ex;
        }
    }


    protected function checkOption( string $i_stOption, bool|string $i_bstFlag ) : bool {
        if ( ! array_key_exists( $i_stOption, static::OPTIONS ) ) {
            throw new LogicException( "Option {$i_stOption} checked not defined." );
        }
        if ( is_null( $this->nrOptions ) ) {
            throw new LogicException( 'Options checked but not yet handled.' );
        }
        return ( $this->nrOptions[ $i_stOption ] ?? static::OPTIONS[ $i_stOption ] ) === $i_bstFlag;
    }


    protected function cli() : Interpreter {
        return $this->cli;
    }


    /**
     * This is a hook for subclasses to catch recoverable exceptions that
     * occur while running the command that they can recover from. Always
     * call parent::handleException() if you override this method to get
     * clean handling of argument exceptions.
     */
    protected function handleException( Exception $i_ex ) : bool {
        if ( $i_ex instanceof ArgumentException && $this->cli()->handleArgumentException( $i_ex ) ) {
            if ( is_string( static::HELP ) ) {
                echo 'Usage: ' . $this->getUsage(), "\n";
            }
            return true;
        }
        return false;
    }


    /**
     * This arguably should be called automatically from runOuter, but some existing commands that
     * use this code expect that it hasn't been. This may change in the future after
     * we are able to find and update those.
     */
    protected function handleOptions( Arguments $io_args ) : void {
        $this->nrOptions = $io_args->handleOptionsDefined( static::OPTIONS );
    }


    /**
     * @param array<string, mixed> $i_rContext
     * @deprecated Remove at 1.1.0.
     */
    protected function logError( string|Stringable $i_stError, array $i_rContext = [] ) : void {
        $this->cli()->error( $i_stError, $i_rContext );
    }


    /**
     * @param array<string, mixed> $i_rContext
     * @deprecated Remove at 1.1.0.
     */
    protected function logInfo( string|Stringable $i_stInfo, array $i_rContext = [] ) : void {
        $this->cli()->info( $i_stInfo, $i_rContext );
    }


    /**
     * @param array<string, mixed> $i_rContext
     * @deprecated Remove at 1.1.0.
     */
    protected function logWarning( string $i_stWarning, array $i_rContext = [] ) : void {
        $this->cli()->warning( $i_stWarning, $i_rContext );
    }


}
