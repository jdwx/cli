<?php


declare( strict_types = 1 );


class MyMultiwordTestCommand extends \JDWX\CLI\Command {


    protected const COMMAND = 'my multiword test command';


    public function run( \JDWX\Args\Arguments $args ) : void {
        $args->end();
    }


}
