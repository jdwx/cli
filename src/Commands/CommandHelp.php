<?php


declare( strict_types = 1 );


namespace JDWX\CLI\Commands;


use JDWX\Args\Arguments;
use JDWX\CLI\Command;


class CommandHelp extends Command {


    protected const string COMMAND = 'help';

    protected const string HELP    = 'Show available commands.';

    protected const string USAGE   = 'help [command...]';


    protected function run( Arguments $args ) : void {
        if ( $args->empty() ) {
            $this->cli()->showHelp();
            return;
        }
        $rArgs = $args->endWithArray();
        $this->cli()->showHelp( $rArgs );
    }


}
