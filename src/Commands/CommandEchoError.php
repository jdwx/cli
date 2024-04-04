<?php


declare( strict_types = 1 );


namespace JDWX\CLI\Commands;


use JDWX\Args\Arguments;
use JDWX\CLI\Command;


class CommandEchoError extends Command {


    protected const COMMAND = 'echo error';
    protected const HELP = 'Echo arguments to stderr.';
    protected const USAGE = 'echo error <string...>';


    public function run( Arguments $args ) : void {
        $st = $args->endWithString();
        error_log( $st );
    }


}
