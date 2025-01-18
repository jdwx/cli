<?php


declare( strict_types = 1 );


namespace JDWX\CLI\Commands;


use JDWX\Args\Arguments;
use JDWX\CLI\Command;


class CommandSet extends Command {


    protected const COMMAND = 'set';
    protected const HELP = 'Set a variable.';
    protected const USAGE = 'set <variable> <value...>';


    protected function run( Arguments $args ) : void {
        $stKey = $args->shiftStringEx( "Missing variable name" );
        $stValue = $args->endWithStringEx( "Missing value" );
        $this->cli()->setVariable( $stKey, $stValue );
    }


}
