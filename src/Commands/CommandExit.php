<?php


declare( strict_types = 1 );


namespace JDWX\CLI\Commands;


use JDWX\Args\Arguments;
use JDWX\CLI\Command;


class CommandExit extends Command {


    protected const COMMAND = 'exit';

    protected const ALIASES = [ 'quit' ];

    protected const HELP    = 'Exit the program.';


    protected function run( Arguments $args ) : void {
        $args->end();
        $this->cli()->setContinue( false );
    }


}
