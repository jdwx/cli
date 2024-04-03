<?php


declare( strict_types = 1 );


namespace JDWX\CLI;


use Psr\Log\LogLevel;
use Stringable;


class StderrLogger extends RelayLogger {


    /**
     * @inheritDoc
     */
    public function log( $level, Stringable|string $message, array $context = [] ) : void {
        $stLevel = match( $level ) {
            LOG_EMERG, LogLevel::EMERGENCY => "EMERGENCY",
            LOG_ALERT, LogLevel::ALERT => "ALERT",
            LOG_CRIT, LogLevel::CRITICAL => "CRITICAL",
            LOG_ERR, LogLevel::ERROR => "ERROR",
            LOG_WARNING, LogLevel::WARNING => "WARNING",
            LOG_NOTICE, LogLevel::NOTICE => "NOTICE",
            LOG_INFO, LogLevel::INFO => "INFO",
            LOG_DEBUG, LogLevel::DEBUG => "DEBUG",
            default => "UNKNOWN",
        };
        $stMessage = $message instanceof Stringable ? $message->__toString() : $message;
        if ( ! empty( $context ) ) {
            $stMessage .= " " . json_encode( $context );
        }
        error_log( "{$stLevel}: {$stMessage}" );
    }


}
