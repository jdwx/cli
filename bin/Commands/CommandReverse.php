<?php


declare( strict_types = 1 );


namespace JDWX\CLI\Commands;


use JDWX\Args\Arguments;
use JDWX\CLI\Command;


/**
 * This is an example command that reverses the arguments and echoes them to the output stream.
 * It's used from php_sh.php to demonstrate how to add a command.
 */
class CommandReverse extends Command {


    protected const string COMMAND = 'reverse';

    protected const string HELP    = 'Reverse the arguments and echo them to the output stream.';

    protected const string USAGE   = 'reverse <string>';


    protected function run( Arguments $args ) : void {
        $bFirst = true;
        while ( $st = $args->shiftString() ) {
            if ( ! $bFirst ) {
                echo ' ';
            }
            echo strrev( $st );
            $bFirst = false;
        }
        echo "\n";
    }


}
