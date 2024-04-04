<?php


declare( strict_types = 1 );


namespace JDWX\CLI;


use JDWX\Args\Arguments;


/**
 * You can use this base class if you do not require special handling of the
 * arguments.  If you do, you should extend AbstractCommand instead and
 * create your own abstract subclass with its own abstract run() method
 * with the proper signature.
 */
abstract class Command extends AbstractCommand {


    abstract protected function run( Arguments $args ) : void;


}
