<?php


declare( strict_types = 1 );


use PHPUnit\Framework\TestCase;


require_once __DIR__ . '/MyTestLogger.php';


class RelayLoggerTest extends TestCase {


    public function testDebug() : void {
        $log = new MyTestLogger();
        $rContext = [ 'foo' => 'bar' ];
        $log->debug( "Test", $rContext );
        self::assertSame( LOG_DEBUG, $log->level );
        self::assertSame( "Test", $log->message );
        self::assertSame( $rContext, $log->context );
    }


    public function testInfo() : void {
        $log = new MyTestLogger();
        $rContext = [ 'foo' => 'bar' ];
        $log->info( "Test", $rContext );
        self::assertSame( LOG_INFO, $log->level );
        self::assertSame( "Test", $log->message );
        self::assertSame( $rContext, $log->context );
    }


    public function testNotice() : void {
        $log = new MyTestLogger();
        $rContext = [ 'foo' => 'bar' ];
        $log->notice( "Test", $rContext );
        self::assertSame( LOG_NOTICE, $log->level );
        self::assertSame( "Test", $log->message );
        self::assertSame( $rContext, $log->context );
    }


    public function testWarning() : void {
        $log = new MyTestLogger();
        $rContext = [ 'foo' => 'bar' ];
        $log->warning( "Test", $rContext );
        self::assertSame( LOG_WARNING, $log->level );
        self::assertSame( "Test", $log->message );
        self::assertSame( $rContext, $log->context );
    }


    public function testError() : void {
        $log = new MyTestLogger();
        $rContext = [ 'foo' => 'bar' ];
        $log->error( "Test", $rContext );
        self::assertSame( LOG_ERR, $log->level );
        self::assertSame( "Test", $log->message );
        self::assertSame( $rContext, $log->context );
    }


    public function testCritical() : void {
        $log = new MyTestLogger();
        $rContext = [ 'foo' => 'bar' ];
        $log->critical( "Test", $rContext );
        self::assertSame( LOG_CRIT, $log->level );
        self::assertSame( "Test", $log->message );
        self::assertSame( $rContext, $log->context );
    }


    public function testAlert() : void {
        $log = new MyTestLogger();
        $rContext = [ 'foo' => 'bar' ];
        $log->alert( "Test", $rContext );
        self::assertSame( LOG_ALERT, $log->level );
        self::assertSame( "Test", $log->message );
        self::assertSame( $rContext, $log->context );
    }


    public function testEmergency() : void {
        $log = new MyTestLogger();
        $rContext = [ 'foo' => 'bar' ];
        $log->emergency( "Test", $rContext );
        self::assertSame( LOG_EMERG, $log->level );
        self::assertSame( "Test", $log->message );
        self::assertSame( $rContext, $log->context );
    }


}
