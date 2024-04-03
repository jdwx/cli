<?php


declare( strict_types = 1 );


use PHPUnit\Framework\TestCase;


require_once __DIR__ . '/MyTestApplication.php';


class ApplicationTest extends TestCase {


    public function testArgs() : void {
        $app = new MyTestApplication([ 'test/command', 'foo', 'bar' ]);
        self::assertSame( 'foo', $app->args()->shiftStringEx() );
        self::assertSame( 'bar', $app->args()->shiftStringEx() );
    }


    public function testHandleOptions() : void {
        $app = new MyTestApplication([ 'test/command', '--foo=bar' ]);
        $app->run();
        self::assertSame( 'bar', $app->foo );
        $app = new MyTestApplication([ 'test/command', '--no-foo' ]);
        $app->run();
        self::assertSame( false, $app->foo );
        $app = new MyTestApplication([ 'test/command', '--bar' ]);
        $app->run();
        self::assertInstanceOf( InvalidArgumentException::class, $app->ex );
    }


    public function testHandleOptionsForValue() : void {
        $app = new MyTestApplication([ 'test/command', '--bar=baz' ]);
        $app->run();
        self::assertInstanceOf( InvalidArgumentException::class, $app->ex );
    }


    public function testLog() : void {
        $log = new MyTestLogger();
        $app = new MyTestApplication([ 'test/command' ], $log );
        $rContext = [ 'foo' => 'bar' ];
        $app->logWarning( 'TEST_MESSAGE', $rContext );
        self::assertSame( LOG_WARNING, $log->level );
        self::assertSame( 'TEST_MESSAGE', $log->message );
        self::assertSame( $rContext, $log->context );
    }


    public function testLogDebugForDisabled() : void {
        $log = new MyTestLogger();
        $app = new MyTestApplication([ 'test/command' ], $log );
        $app->logDebug( 'TEST_MESSAGE' );
        self::assertSame( null, $log->level );
        self::assertSame( null, $log->message );
        self::assertSame( null, $log->context );
    }


    public function testLogDebugForEnabled() : void {
        $log = new MyTestLogger();
        $app = new MyTestApplication([ 'test/command', '--debug' ], $log );
        $app->run();
        $app->logDebug( 'TEST_MESSAGE' );
        self::assertSame( LOG_DEBUG, $log->level );
        self::assertSame( 'TEST_MESSAGE', $log->message );
        self::assertSame( [], $log->context );
    }


    public function testLogDebugForEnabledExplicitly() : void {
        $log = new MyTestLogger();
        $app = new MyTestApplication([ 'test/command', '--debug=yes' ], $log );
        $app->run();
        $app->logDebug( 'TEST_MESSAGE' );
        self::assertSame( LOG_DEBUG, $log->level );
        self::assertSame( 'TEST_MESSAGE', $log->message );
        self::assertSame( [], $log->context );
    }


    public function testRun() : void {
        $st = new MyTestApplication([ 'test/command' ]);
        $st->run();
        self::assertSame( "command", $st->getCommand() );
        self::assertSame( "test/command", $st->getCommandPath() );
        self::assertSame( $st->iExitStatus, 0 );
    }


}
