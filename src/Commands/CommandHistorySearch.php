<?php


declare( strict_types = 1 );


namespace JDWX\CLI\Commands;


use JDWX\Args\Arguments;
use JDWX\CLI\Command;


class CommandHistorySearch extends Command {


    protected const COMMAND = 'history search';
    protected const HELP = 'Search command history.';
    protected const USAGE = '<string>';
    public const HISTORY = false;


    protected function run( Arguments $args ) : void {
        $stSearch = $args->endWithStringEx( "Missing search string." );
        $rHistory = $this->cli()->getHistory();
        $rMatches = array_filter( $rHistory, function ( $stCommand ) use ( $stSearch ) {
            return str_contains( $stCommand, $stSearch );
        } );
        $uCount = count( $rMatches );
        echo "History has {$uCount} matching command", ($uCount == 1 ) ? "" : "s", ":\n";
        foreach ( $rMatches as $uIndex => $stCommand ) {
            $stIndex = str_pad( "$uIndex", 3, " ", STR_PAD_LEFT );
            echo "{$stIndex} {$stCommand}\n";
        }
    }


}
