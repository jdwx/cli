<?php


declare( strict_types = 1 );


namespace JDWX\CLI\Commands;


use JDWX\Args\Arguments;
use JDWX\CLI\Command;


class CommandHistory extends Command {


    protected const COMMAND = 'history';

    protected const HELP    = 'Show command history.';

    protected const USAGE   = 'history';

    public const    HISTORY = false;


    protected function run( Arguments $args ) : void {
        $args->end();
        $rHistory = $this->cli()->getHistory();
        $uCount = count( $rHistory );
        echo "History has $uCount command", ( 1 === $uCount ) ? '' : 's', ":\n";
        foreach ( $rHistory as $uIndex => $sCommand ) {
            $stIndex = str_pad( "$uIndex", 3, ' ', STR_PAD_LEFT );
            echo "{$stIndex} $sCommand\n";
        }
    }


}
