<?php


declare( strict_types = 1 );


use JDWX\Args\Arguments;
use PHPUnit\Framework\TestCase;


require __DIR__ . '/MyTestCommand.php';
require __DIR__ . '/MyTestInterpreter.php';



class CommandTest extends TestCase {


    public function testAskYN() : void {
        $cli = new MyTestInterpreter();
        $command = new MyTestCommand( $cli );
        $cli->yn = true;
        self::assertTrue( $command->askYN( 'foo' ) );
        $cli->yn = false;
        self::assertFalse( $command->askYN( 'foo' ) );
    }


    public function testCheckOption() : void {
        $cli = new MyTestInterpreter();

        $args = new Arguments([]);
        $command = new MyTestCommand( $cli );
        $command->runOuter( $args );
        self::assertTrue( $command->checkOptionRelay( 'foo', 'foo_default' ) );

        $args = new Arguments( [ '--foo' ] );
        $command = new MyTestCommand( $cli );
        $command->runOuter( $args );
        self::assertTrue( $command->checkOptionRelay( 'foo', true ) );

        $args = new Arguments( [ '--foo=bar' ] );
        $command = new MyTestCommand( $cli );
        $command->runOuter( $args );
        self::assertTrue( $command->checkOptionRelay( 'foo', 'bar' ) );
    }


    public function testCheckOptionForNotDefined() : void {
        $cli = new MyTestInterpreter();
        $command = new MyTestCommand( $cli );
        self::expectException( LogicException::class );
        $command->checkOptionRelay( 'bar', 'baz' );
    }


    public function testCheckOptionForTooEarly() : void {
        $cli = new MyTestInterpreter();
        $command = new MyTestCommand( $cli );
        self::expectException( LogicException::class );
        $command->checkOptionRelay( 'foo', 'wont_work' );
    }


    public function testRun() : void {
        $cli = new MyTestInterpreter();
        $command = new MyTestCommand( $cli );
        $args = new Arguments([ 'foo', 'bar' ]);
        $command->runOuter( $args );
        self::assertSame( $args, $command->args );
    }


}
