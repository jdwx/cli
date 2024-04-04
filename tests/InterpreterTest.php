<?php


declare( strict_types = 1 );


use PHPUnit\Framework\TestCase;


class InterpreterTest extends TestCase {


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
        self::assertCount( 2, $r );
        self::assertSame( "echo", $r[ 0 ] );
        self::assertSame( "exit", $r[ 1 ] );
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
        $cli = new MyTestInterpreter();
        $cli->readLines = [ "e" ];
        ob_start();
        $cli->run();
        $st = ob_get_clean();
        self::assertStringContainsString( 'Ambiguous command: e (2)', $st );
    }


    public function testShowHelp() : void {
        $cli = new MyTestInterpreter();
        ob_start();
        $cli->showHelp([ 'echo' ]);
        $st = ob_get_clean();
        self::assertStringContainsString( 'Echo the arguments', $st);
    }


}
