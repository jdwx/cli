<?php


declare( strict_types = 1 );


use JDWX\CLI\CommandMatcher;
use PHPUnit\Framework\TestCase;


class CommandMatcherTest extends TestCase {


    public function testMatch() : void {
        $rInput = [ 'show', 'foo' ];
        $rCommands = [ 'show', 'walk' ];
        $rExpected = [ 'show' ];
        $rActual = CommandMatcher::match( $rInput, $rCommands );
        self::assertEquals( $rExpected, $rActual );
    }


    public function testMatchForNoMatch() : void {
        $rInput = [ 'show', 'foo' ];
        $rCommands = [ 'walk' ];
        $rExpected = [];
        $rActual = CommandMatcher::match( $rInput, $rCommands );
        self::assertEquals( $rExpected, $rActual );
    }


    public function testMatchForAmbiguous() : void {
        $rInput = [ 'qu', 'foo' ];
        $rCommands = [ 'qux', 'quux', 'goose' ];
        $rExpected = [ 'qux', 'quux' ];
        $rActual = CommandMatcher::match( $rInput, $rCommands );
        self::assertEquals( $rExpected, $rActual );
    }


    public function testWinnow() : void {
        $rInput = [ 'foo', 'ba' ];
        $rCommands = [ 'foo', 'foo bar' ];
        $rExpected = [ 'foo bar' ];
        $rActual = CommandMatcher::winnow( $rInput, $rCommands );
        self::assertEquals( $rExpected, $rActual );
    }


    public function testWinnowForAmbiguous() : void {
        $rInput = [ 'foo', 'ba' ];
        $rCommands = [ 'foo bar', 'foo baz' ];
        $rActual = CommandMatcher::winnow( $rInput, $rCommands );
        self::assertEquals( $rCommands, $rActual );
    }


    public function testWinnowForAmbiguousImprovement() : void {
        $rInput = [ 'foo', 'ba' ];
        $rCommands = [ 'foo bar', 'foo baz', 'foo' ];
        $rExpected = [ 'foo bar', 'foo baz' ];
        $rActual = CommandMatcher::winnow( $rInput, $rCommands );
        self::assertEquals( $rExpected, $rActual );
    }


    public function testWinnowForShorterCommand() : void {
        $rInput = [ 'foo', 'qux' ];
        $rCommands = [ 'foo', 'foobar' ];
        $rExpected = [ 'foo' ];
        $rActual = CommandMatcher::winnow( $rInput, $rCommands );
        self::assertEquals( $rExpected, $rActual );
    }


    public function testWinnowForAmbiguousWithArgs() : void {
        $rInput = [ 'foo', 'ba', 'qux' ];
        $rCommands = [ 'foo', 'foo baz', 'foo baz zok' ];
        $rExpected = [ 'foo baz' ];
        $rActual = CommandMatcher::winnow( $rInput, $rCommands );
        self::assertEquals( $rExpected, $rActual );
    }


}
