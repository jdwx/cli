<?php


declare( strict_types = 1 );


namespace JDWX\CLI;


use JDWX\Args\Arguments;


abstract class Command extends AbstractCommand {


    abstract public function run( Arguments $args ) : void;


}
