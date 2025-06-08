<?php


declare( strict_types = 1 );


use JDWX\Args\Arguments;
use JDWX\Log\BufferLogger;
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


    public function testCheckOptionForDefaultValue() : void {
        $cli = new MyTestInterpreter();
        $args = new Arguments( [] );
        $command = new MyTestCommand( $cli );
        $command->runOuter( $args );
        self::assertTrue( $command->checkOptionRelay( 'foo', 'foo_default' ) );
    }


    public function testCheckOptionForNotDefined() : void {
        $cli = new MyTestInterpreter();
        $command = new MyTestCommand( $cli );
        self::expectException( LogicException::class );
        $command->checkOptionRelay( 'bar', 'baz' );
    }


    public function testCheckOptionForStringValue() : void {
        $cli = new MyTestInterpreter();
        $args = new Arguments( [ '--foo=bar' ] );
        $command = new MyTestCommand( $cli );
        $command->runOuter( $args );
        self::assertTrue( $command->checkOptionRelay( 'foo', 'bar' ) );
    }


    public function testCheckOptionForTooEarly() : void {
        $cli = new MyTestInterpreter();
        $command = new MyTestCommand( $cli );
        self::expectException( LogicException::class );
        $command->checkOptionRelay( 'foo', 'wont_work' );
    }


    public function testCheckOptionForTrueValueOnBooleanOption() : void {
        $cli = new MyTestInterpreter();
        $args = new Arguments( [ '--bar' ] );
        $command = new MyTestCommand( $cli );
        $command->runOuter( $args );
        self::assertTrue( $command->checkOptionRelay( 'bar', true ) );
    }


    public function testCheckOptionForTrueValueOnStringOption() : void {
        $cli = new MyTestInterpreter();
        $args = new Arguments( [ '--foo' ] );
        $command = new MyTestCommand( $cli );
        $command->runOuter( $args );
        self::assertTrue( $command->checkOptionRelay( 'foo', 'foo_default' ) );
    }


    public function testMissingArgument() : void {
        $log = new BufferLogger();
        $cli = new MyTestInterpreter( i_log: $log );
        $command = new MyTestCommand( $cli, function ( Arguments $args ) {
            $args->shiftStringEx();
        } );
        $args = new Arguments( [] );
        $command->runOuter( $args );
        $le = $log->shiftLog();
        self::assertSame( 'Missing argument', $le->message );
    }


    public function testRun() : void {
        $cli = new MyTestInterpreter();
        $command = new MyTestCommand( $cli );
        $args = new Arguments( [ 'foo', 'bar' ] );
        $command->runOuter( $args );
        self::assertSame( $args, $command->args );
    }


}
