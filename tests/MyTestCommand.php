<?php


declare( strict_types = 1 );


use JDWX\Args\Arguments;
use JDWX\CLI\Command;


class MyTestCommand extends Command {


    protected const COMMAND = 'my';
    protected const OPTIONS = [ 'foo' => 'foo_default' ];


    public ?Arguments $args = null;


    public function checkOptionRelay( string $i_stOption, bool|string $i_stValue ) : bool {
        return $this->checkOption( $i_stOption, $i_stValue );
    }


    protected function run( Arguments $args ) : void {
        $this->handleOptions( $args );
        $this->args = $args;
    }


}
