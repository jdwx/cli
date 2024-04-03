<?php


declare( strict_types = 1 );


namespace JDWX\CLI\Commands;


use JDWX\Args\Arguments;
use JDWX\CLI\Command;


class CommandHelp extends Command {


    protected const COMMAND = "help";
    protected const HELP = "Show available commands.";
    protected const USAGE = "help [command...]";


    public function run( Arguments $args ) : void {
        if ( $args->empty() ) {
            $this->cli()->showHelp();
            return;
        }
        $rArgs = $args->endWithArray();
        $this->cli()->showHelp( $rArgs );
    }


}
