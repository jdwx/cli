<?php


declare( strict_types = 1 );


use JDWX\Args\Arguments;
use JDWX\CLI\AbstractCommand;


class MyMultiwordTestCommand extends AbstractCommand {


    protected const COMMAND = 'my multiword test command';


    protected function run( Arguments $args ) : void {
        $args->end();
    }


}
