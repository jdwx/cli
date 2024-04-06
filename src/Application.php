<?php


declare( strict_types = 1 );


namespace JDWX\CLI;


use Exception;
use InvalidArgumentException;
use JDWX\Args\ArgumentParser;
use JDWX\Args\Arguments;
use JDWX\Args\BadArgumentException;
use Psr\Log\LoggerInterface;


abstract class Application {


    private Arguments $args;

    protected bool $bDebug = false;

    protected int $pid;

    protected string $stCommand;

    protected string $stCommandPath;


    /** @param string[]|Arguments|null $i_argv */
    public function __construct( array|Arguments|null $i_argv = null, private readonly ?LoggerInterface $log = null ) {
        $this->pid = getmypid();
        if ( $i_argv instanceof Arguments ) {
            $this->args = $i_argv;
        } else {
            $this->args = $this->newArguments( $i_argv );
        }
        $this->stCommandPath = $this->args->shiftStringEx();
        $this->stCommand = basename( $this->stCommandPath );
    }


    public function args() : Arguments {
        return $this->args;
    }


    protected function debugCleanup() : void {
    }


    protected function debugSetup() : void {
    }


    /** @noinspection PhpNoReturnAttributeCanBeAddedInspection */
    protected function exit( int $i_iStatus ) : void {
        exit( $i_iStatus );
    }


    public function getCommand() : string {
        return $this->stCommand;
    }


    public function getCommandPath() : string {
        return $this->stCommandPath;
    }


    protected function handleException( Exception $i_ex ) : void {
        if ( $i_ex instanceof InvalidArgumentException ) {
            $this->logError( $i_ex->getMessage() );
            return;
        }
        if ( $i_ex instanceof BadArgumentException ) {
            $this->logError( $i_ex->getMessage() . ": " . $i_ex->getValue() );
            return;
        }
        $this->logError( $i_ex->getMessage(), [
            "class" => $i_ex::class,
            "code" => $i_ex->getCode(),
            "file" => $i_ex->getFile(),
            "line" => $i_ex->getLine(),
        ] );
    }


    public function handleOption( string $i_stOption, bool|string $i_bstValue ) : void {
        $method = "handleOption_" . strtolower( $i_stOption );
        if ( method_exists( $this, $method ) ) {
            $this->$method( $i_bstValue );
            return;
        }
        if ( $i_bstValue === true || $i_bstValue === false ) {
            throw new InvalidArgumentException( "Unknown option \"{$i_stOption}\"" );
        }
        throw new InvalidArgumentException( "Unknown option \"{$i_stOption}" . ( $i_bstValue ? "({$i_bstValue})" : "" ) . "\"" );
    }


    /**
     * @noinspection PhpMethodNamingConventionInspection
     * @noinspection PhpUnused
     */
    public function handleOption_debug( bool|string $i_bDebug ) : void {
        if ( ! is_bool( $i_bDebug ) ) {
            $i_bDebug = ArgumentParser::parseBool( $i_bDebug );
        }
        $this->bDebug = $i_bDebug;
    }


    public function handleOptions() : void {
        $rOptions = $this->args->handleOptions();
        foreach ( $rOptions as $stOption => $stValue ) {
            $this->handleOption( $stOption, $stValue );
        }
    }


    public function log( int $i_iPriority, string $i_stMessage, array $i_rContext = [] ) : void {
        if ( ! $this->log instanceof LoggerInterface ) {
            return;
        }
        if ( LOG_DEBUG === $i_iPriority && ! $this->bDebug ) {
            return;
        }
        $this->log->log( $i_iPriority, $i_stMessage, $i_rContext );
    }


    public function logDebug( string $i_stMessage, array $i_rContext = []  ) : void {
        $this->log( LOG_DEBUG, $i_stMessage, $i_rContext );
    }


    public function logError( string $i_stMessage, array $i_rContext = []  ) : void {
        $this->log( LOG_ERR, $i_stMessage, $i_rContext );
    }


    public function logInfo( string $i_stMessage, array $i_rContext = []  ) : void {
        $this->log( LOG_INFO, $i_stMessage, $i_rContext );
    }


    public function logWarning( string $i_i_stMessage, array $i_rContext = []  ) : void {
        $this->log( LOG_WARNING, $i_i_stMessage, $i_rContext );
    }


    abstract protected function main() : int;


    protected function newArguments( ?array $i_argv ) : Arguments {
        global $argv;
        if ( is_null( $i_argv ) ) {
            $i_argv = $argv;
        }
        return new Arguments( $i_argv );
    }


    final public function run() : void {
        try {
            $this->setup();
            $this->handleOptions();
            $this->logDebug( "application begins" );
            if ( $this->bDebug ) $this->debugSetup();
            $rc = $this->main();
            if ( $this->bDebug ) $this->debugCleanup();
            $this->logDebug( "application ends with {$rc}" );
            flush();
            $this->exit( $rc );
        } catch ( Exception $ex ) {
            $this->handleException( $ex );
            $this->exit( 10 );
        }
    }


    public function setup() : void {
    }


}
