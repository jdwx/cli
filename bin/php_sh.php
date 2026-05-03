<?php


declare( strict_types = 1 );


require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/Commands/CommandReverse.php';


use JDWX\CLI\Bin\Commands\CommandReverse;
use JDWX\CLI\Interpreter;
use JDWX\Log\StderrLogger;


( new class( $argv ) extends Interpreter {


    /** @param list<string> $i_argv */
    public function __construct( array $i_argv ) {
        $log = new StderrLogger();
        parent::__construct( '$ ', $i_argv, $log );
        $this->addCommandClass( CommandReverse::class );
    }


} )->run();

