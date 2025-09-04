<?php


declare( strict_types = 1 );


namespace JDWX\CLI\Tests;


use JDWX\Args\Arguments;
use JDWX\CLI\Command;
use JDWX\CLI\Interpreter;


class MyTestCommand extends Command {


    protected const COMMAND = 'my';

    protected const OPTIONS = [ 'foo' => 'foo_default', 'bar' => false ];


    public ?Arguments $args = null;

    /** @var ?callable */
    private $fnCallable;


    public function __construct( Interpreter $i_cli, ?callable $i_fnCallable = null ) {
        parent::__construct( $i_cli );
        $this->fnCallable = $i_fnCallable;
    }


    public function checkOptionRelay( string $i_stOption, bool|string $i_stValue ) : bool {
        return $this->checkOption( $i_stOption, $i_stValue );
    }


    protected function run( Arguments $args ) : void {
        $this->handleOptions( $args );
        $this->args = $args;
        if ( $this->fnCallable ) {
            ( $this->fnCallable )( $args );
        }
    }


}
