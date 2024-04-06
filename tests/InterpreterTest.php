<?php


declare( strict_types = 1 );


use PHPUnit\Framework\TestCase;


require_once __DIR__ . '/MyTestInterpreter.php';
require_once __DIR__ . '/MyTestLogger.php';


class InterpreterTest extends TestCase {


    public function testBackQuotes() : void {
        $cli = new MyTestInterpreter();
        $cli->readLines = [ "echo `echo hello`" ];
        ob_start();
        $cli->run();
        $st = trim( ob_get_clean() );
        self::assertSame( "hello", $st );
    }


    public function testEcho() : void {
        $cli = new MyTestInterpreter();
        $cli->readLines = [ "echo hello" ];
        ob_start();
        $cli->run();
        $st = trim( ob_get_clean() );
        self::assertSame( "hello", $st );
    }


    public function testExit() : void {
        $cli = new MyTestInterpreter();
        $cli->readLines = [ "exit", "echo hello" ];
        ob_start();
        $cli->run();
        $st = trim( ob_get_clean() );
        self::assertSame( "", $st );
    }


    public function testReadlineCompletionForAmbiguous() : void {
        $cli = new MyTestInterpreter();
        $cli->lineBuffer = "e";
        $cli->end = strlen( $cli->lineBuffer );
        $r = $cli->readlineCompletion( "unused", 0 );
        self::assertCount( 3, $r );
        self::assertSame( "echo", $r[ 0 ] );
        self::assertSame( "exit", $r[ 1 ] );
        self::assertSame( "expr", $r[ 2 ] );
    }


    public function testReadlineCompletionForMultiword() : void {
        $cli = new MyTestInterpreter();
        $cli->lineBuffer = "my multi";
        $cli->end = strlen( $cli->lineBuffer );
        $r = $cli->readlineCompletion( "unused", 0 );
        self::assertCount( 1, $r );
        $st = $r[ 0 ];
        self::assertEquals( "my multiword test command", $st );
    }


    public function testRun() : void {
        $args = [ 'test/command' ];
        $cli = new MyTestInterpreter( i_argv: $args );
        $cli->run();
        self::assertSame( 0, $cli->status );
    }


    public function testRunForAmbiguous() : void {
        $log = new MyTestLogger();
        $cli = new MyTestInterpreter( i_log: $log );
        $cli->readLines = [ "e" ];
        ob_start();
        $cli->run();
        ob_end_clean();
        self::assertSame( LOG_WARNING, $log->level );
        self::assertStringContainsString( 'Ambiguous', $log->message );
    }


    public function testShowHelp() : void {
        $cli = new MyTestInterpreter();
        ob_start();
        $cli->showHelp([ 'echo' ]);
        $st = ob_get_clean();
        self::assertStringContainsString( 'Echo the arguments', $st);
    }


    public function testVariables() : void {
        $cli = new MyTestInterpreter();
        $cli->readLines = [ 'set x 5', 'echo $x' ];
        ob_start();
        $cli->run();
        $st = trim( ob_get_clean() );
        self::assertSame( '5', $st );
        self::assertSame( '5', $cli->getVariable( 'x' ) );
    }


    public function testVariablesError() : void {
        $log = new MyTestLogger();
        $cli = new MyTestInterpreter( i_log: $log );
        $cli->readLines = [ 'echo $y' ];
        $cli->run();
        self::assertStringContainsString( 'Undefined', $log->message );
    }


}
