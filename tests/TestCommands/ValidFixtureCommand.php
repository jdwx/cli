<?php


declare( strict_types = 1 );


namespace JDWX\CLI\Tests\TestCommands;


use JDWX\Args\Arguments;
use JDWX\CLI\Command;


class ValidFixtureCommand extends Command {


    protected const string COMMAND = 'fixturecmd';

    protected const string HELP    = 'A fixture command for tests.';


    protected function run( Arguments $args ) : void {
        echo 'fixture-output', "\n";
    }


}
