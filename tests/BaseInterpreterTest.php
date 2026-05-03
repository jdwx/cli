<?php


declare( strict_types = 1 );


namespace JDWX\CLI\Tests;


use JDWX\Args\Arguments;
use JDWX\CLI\AbstractCommand;
use JDWX\CLI\BaseInterpreter;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;


#[CoversClass( BaseInterpreter::class )]
final class BaseInterpreterTest extends TestCase {


    public function testAddCommandForDuplicate() : void {
        $cli = new MyTestInterpreter();
        $cli->addCommandRelay( 'example', 'commandExample' );
        $this->expectException( \InvalidArgumentException::class );
        $cli->addCommandRelay( 'example', 'commandExample2' );
    }


    public function testAddCommandForMissingCommand() : void {
        $cli = new class() extends MyTestInterpreter {


            /** @noinspection PhpUnused */
            public function commandExample() : void {
                echo 'Not used.\n';
            }


        };
        $this->expectException( \InvalidArgumentException::class );
        $cli->addCommandRelay( BaseInterpreter::DEFAULT_COMMAND, 'commandExample' );
    }


    public function testAddCommandObjectForMissingCommand() : void {

        $cli = new MyTestInterpreter();
        $cmd = new class( $cli ) extends AbstractCommand {


            protected function run( Arguments $i_args ) : void {}


        };

        $this->expectException( \InvalidArgumentException::class );
        $cli->addCommandObjectRelay( $cmd );
    }


}
