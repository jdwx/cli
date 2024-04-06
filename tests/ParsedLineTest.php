<?php


declare( strict_types = 1 );


use JDWX\CLI\LineParser;
use PHPUnit\Framework\TestCase;


class ParsedLineTest extends TestCase {


    public function testGetOriginal() : void {
        $x = LineParser::parseLine( "foo bar baz" );
        self::assertEquals( "foo bar baz", $x->getOriginal() );
    }


    public function testGetSegments() : void {
        $x = LineParser::parseLine( "foo bar baz" );
        $r = $x->getSegments();
        self::assertEquals( "foo", $r[ 0 ] );
        self::assertEquals( "bar", $r[ 1 ] );
        self::assertEquals( "baz", $r[ 2 ] );
        self::assertCount( 3, $r );
    }


    public function testSubstVariables() : void {
        $x = LineParser::parseLine( "foo \$bar baz" );
        self::assertTrue( $x->substVariables( [ "bar" => "bar" ] ) );
        self::assertEquals( "foo bar baz", $x->getProcessed() );
    }


    public function testSubstVariablesForUndefinedVariable() : void {
        $x = LineParser::parseLine( "foo \$bar baz" );
        $y = $x->substVariables( [] );
        self::assertIsString( $y );
        self::assertStringContainsString( "Undefined", $y );
    }


}
