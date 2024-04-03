<?php


declare( strict_types = 1 );


use JDWX\CLI\RelayLogger;


class MyTestLogger extends RelayLogger {


    public ?int $level = null;
    public ?string $message = null;
    public ?array $context = null;


    public function log( $level, Stringable|string $message, array $context = [] ) : void {
        $this->level = $level;
        $this->message = $message;
        $this->context = $context;
    }


}
