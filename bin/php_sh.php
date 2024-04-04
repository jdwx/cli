<?php


declare( strict_types = 1 );


require_once __DIR__ . '/../vendor/autoload.php';


use JDWX\CLI\Commands\CommandEchoError;
use JDWX\CLI\Interpreter;
use JDWX\CLI\StderrLogger;


(new class( $argv ) extends Interpreter {


    public function __construct( array $i_argv ) {
        $log = new StderrLogger();
        parent::__construct( "$ ", $i_argv, $log );
        $this->addCommandClass( CommandEchoError::class );
    }


})->run();

