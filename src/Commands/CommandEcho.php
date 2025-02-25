<?php


declare( strict_types = 1 );


namespace JDWX\CLI\Commands;


use JDWX\Args\Arguments;
use JDWX\CLI\Command;


class CommandEcho extends Command {


    protected const COMMAND = "echo";
    protected const HELP = "Echo the arguments to the output stream.";
    protected const USAGE = "echo <string>";


    protected function run( Arguments $args ) : void {
        $st = $args->endWithString();
        echo $st, "\n";
    }


}
