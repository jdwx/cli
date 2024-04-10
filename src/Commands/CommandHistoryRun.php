<?php


declare( strict_types = 1 );


namespace JDWX\CLI\Commands;


use JDWX\Args\Arguments;
use JDWX\CLI\Command;


class CommandHistoryRun extends Command {


    protected const COMMAND = 'history run';
    protected const HELP = 'Run a command from the history.';
    protected const USAGE = '<index>';
    public const HISTORY = false;


    protected function run( Arguments $args ) : void {
        $uIndex = $args->shiftUnsignedIntegerEx();
        $args->end();
        $rHistory = $this->cli()->getHistory();
        if ( ! array_key_exists( $uIndex, $rHistory ) ) {
            $this->error( "History index {$uIndex} is out of range." );
            return;
        }
        $stCommand = $rHistory[ $uIndex ];
        $this->info( "[$stCommand]" );
        $this->cli()->handleCommand( $stCommand );

    }





}
