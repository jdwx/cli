<?php


declare( strict_types = 1 );


namespace JDWX\CLI;


use Psr\Log\LoggerInterface;
use Stringable;


abstract class RelayLogger implements LoggerInterface {


    /**
     * @inheritDoc
     */
    public function emergency( Stringable|string $message, array $context = [] ) : void {
        $this->log( LOG_EMERG, $message, $context );
    }


    /**
     * @inheritDoc
     */
    public function alert( Stringable|string $message, array $context = [] ) : void {
        $this->log( LOG_ALERT, $message, $context );
    }


    /**
     * @inheritDoc
     */
    public function critical( Stringable|string $message, array $context = [] ) : void {
        $this->log( LOG_CRIT, $message, $context );
    }


    /**
     * @inheritDoc
     */
    public function error( Stringable|string $message, array $context = [] ) : void {
        $this->log( LOG_ERR, $message, $context );
    }


    /**
     * @inheritDoc
     */
    public function warning( Stringable|string $message, array $context = [] ) : void {
        $this->log( LOG_WARNING, $message, $context );
    }


    /**
     * @inheritDoc
     */
    public function notice( Stringable|string $message, array $context = [] ) : void {
        $this->log( LOG_NOTICE, $message, $context );
    }


    /**
     * @inheritDoc
     */
    public function info( Stringable|string $message, array $context = [] ) : void {
        $this->log( LOG_INFO, $message, $context );
    }


    /**
     * @inheritDoc
     */
    public function debug( Stringable|string $message, array $context = [] ) : void {
        $this->log( LOG_DEBUG, $message, $context );
    }


}
