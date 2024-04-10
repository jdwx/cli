<?php


declare( strict_types = 1 );


namespace JDWX\CLI;


use JDWX\App\TRelayLogger;
use JDWX\Args\Arguments;
use JDWX\Args\BadArgumentException;
use JDWX\Args\ExtraArgumentsException;
use LogicException;
use Psr\Log\LoggerInterface;
use Stringable;


/**
 * This class is the base class for all commands.  It provides the basic
 * structure for a command, including the command name, aliases, help text,
 * usage text, and options.  It also provides a method for running the command
 * and supports checking options.
 *
 * If you need to handle arguments in a special way (i.e., with a subclass of
 * Arguments), you should create an abstract subclass of this with its own abstract
 * run() method with the proper signature.  If not, you can use the Command class
 * instead.
 */
abstract class AbstractCommand implements LoggerInterface {


    use TRelayLogger;


    protected const COMMAND = "____OVERLOAD_ME____";
    protected const ALIASES = [];
    protected const HELP = null;
    protected const USAGE = null;
    protected const OPTIONS = [
        /** If options are used they are of the form "key" => "default_value". */
    ];

    /**
     * If true, the command is added to history when run. This is almost always
     * desirable unless the command manipulates the history itself.
     */
    public const HISTORY = true;


    private Interpreter $cli;
    private ?array $nrOptions = null;


    public function __construct( Interpreter $i_cli ) {
        $this->cli = $i_cli;
    }


    public function askYN( string $i_stPrompt ) : bool {
        return $this->cli()->askYN( $i_stPrompt );
    }


    protected function checkOption( string $i_stOption, bool|string $i_bstFlag ) : bool {
        if ( ! array_key_exists( $i_stOption, static::OPTIONS ) ) {
            throw new LogicException( "Option {$i_stOption} checked not defined." );
        }
        if ( is_null( $this->nrOptions ) ) {
            throw new LogicException( "Options checked but not yet handled." );
        }
        return ( $this->nrOptions[ $i_stOption ] ?? static::OPTIONS[ $i_stOption ] ) === $i_bstFlag;
    }


    public function getAliases() : array {
        if ( is_array( static::ALIASES ) ) {
            return static::ALIASES;
        } elseif ( is_null( static::ALIASES ) ) {
            return [];
        } elseif ( is_string( static::ALIASES ) ) {
            return [ static::ALIASES ];
        }
        throw new LogicException( "ALIASES must be array, string, or null." );
    }


    public function getCommand() : string {
        return static::COMMAND;
    }


    public function getHelp() : ?string {
        return static::HELP;
    }


    public function getUsage() : ?string {
        $stUsage = static::USAGE ?? "";
        if ( ! str_starts_with( $stUsage, static::COMMAND ) ) {
            $stUsage = trim( static::COMMAND . ' ' . $stUsage );
        }
        return $stUsage;
    }


    /**
     * This arguably should be called automatically from runOuter, but some existing commands that
     * use this code expect that it hasn't been. This may change in the future after
     * we are able to find and update those.
     */
    protected function handleOptions( Arguments $io_args ) : void {
        $this->nrOptions = $io_args->handleOptionsDefined( array_keys( static::OPTIONS ) );
    }


    /**
     * @suppress PhanUndeclaredMethod The run() method must exist, but we can't specify the
     * type of its argument. Users will frequently want to use a specific subclass of Arguments.
     */
    public function runOuter( Arguments $args ) : void {
        assert( method_exists( $this, "run" ), "Command " . static::COMMAND . " has no run method." );
        try {
            $this->run( $args );
        } catch ( BadArgumentException $ex ) {
            $this->error( $ex->getMessage() . " \"" . $ex->getValue() . "\"", [
                "class" => $ex::class,
                "code" => $ex->getCode(),
                "file" => $ex->getFile(),
                "line" => $ex->getLine(),
                "value" => $ex->getValue(),
            ] );
            if ( is_string( static::HELP ) ) {
                echo "Usage: " . $this->getUsage(), "\n";
            }
        } catch ( ExtraArgumentsException $ex ) {
            $this->error( $ex->getMessage(), [
                "class" => $ex::class,
                "code" => $ex->getCode(),
                "file" => $ex->getFile(),
                "line" => $ex->getLine(),
                "args" => $ex->getArguments(),
            ]);
            if ( is_string( static::HELP ) ) {
                echo "Usage: " . $this->getUsage(), "\n";
            }
        }
    }


    protected function cli() : Interpreter {
        return $this->cli;
    }


    public function log( mixed $level, string|Stringable $message, array $context = [] ) : void {
        $this->cli()->log( $level, $message, $context );
    }


    /** @deprecated Remove at 1.1.0. */
    protected function logError( string|Stringable $i_stError, array $i_rContext = [] ) : void {
        $this->cli()->error( $i_stError, $i_rContext );
    }


    /** @deprecated Remove at 1.1.0. */
    protected function logInfo( string|Stringable $i_stInfo, array $i_rContext = [] ) : void {
        $this->cli()->info( $i_stInfo, $i_rContext );
    }


    /** @deprecated Remove at 1.1.0. */
    protected function logWarning( string $i_stWarning, array $i_rContext = [] ) : void {
        $this->cli()->warning( $i_stWarning, $i_rContext );
    }


}
