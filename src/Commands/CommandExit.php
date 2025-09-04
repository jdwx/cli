<?php


declare( strict_types = 1 );


namespace JDWX\CLI\Commands;


use JDWX\Args\Arguments;
use JDWX\CLI\Command;


class CommandExit extends Command {


    protected const string COMMAND = 'exit';
    
    /** @var list<string> */
    protected const array  ALIASES = [ 'quit' ];

    protected const string HELP    = 'Exit the program.';


    protected function run( Arguments $args ) : void {
        $args->end();
        $this->cli()->setContinue( false );
    }


}
