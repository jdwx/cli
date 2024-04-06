<?php


declare( strict_types = 1 );


use JDWX\CLI\Segment;
use PHPUnit\Framework\TestCase;


class ParsedSegmentTest extends TestCase {


    public function testIsComment() : void {
        $x = new JDWX\CLI\ParsedSegment( Segment::UNQUOTED, "foo" );
        self::assertFalse( $x->isComment() );
        $x = new JDWX\CLI\ParsedSegment( Segment::COMMENT, "foo" );
        self::assertTrue( $x->isComment() );
    }


    public function testSubstVariablesForBraces() : void {
        $rVariables = [ 'bar' => 'qux' ];

        $x = new JDWX\CLI\ParsedSegment( Segment::UNQUOTED, "foo" );
        $y = $x->substVariables( [] );
        self::assertTrue( $y );
        self::assertSame( "foo", $x->getProcessed() );
        self::assertSame( "foo", $x->getOriginal() );

        $x = new JDWX\CLI\ParsedSegment( Segment::UNQUOTED, "foo" );
        $y = $x->substVariables( $rVariables );
        self::assertTrue( $y );
        self::assertSame( "foo", $x->getProcessed() );
        self::assertSame( "foo", $x->getOriginal() );

        $x = new JDWX\CLI\ParsedSegment( Segment::UNQUOTED, "foo \${bar} baz" );
        $y = $x->substVariables( $rVariables );
        self::assertTrue( $y );
        self::assertSame( "foo qux baz", $x->getProcessed() );
        self::assertSame( "foo \${bar} baz", $x->getOriginal() );

        $x = new JDWX\CLI\ParsedSegment( Segment::UNQUOTED, "foo {bar} baz" );
        $y = $x->substVariables( $rVariables );
        self::assertTrue( $y );
        self::assertSame( "foo {bar} baz", $x->getProcessed() );
        self::assertSame( "foo {bar} baz", $x->getOriginal() );

    }


    public function testSubstVariablesForBracesWithUnmatchedBrace() : void {
        $rVariables = [ 'bar' => 'qux' ];
        $x = new JDWX\CLI\ParsedSegment( Segment::UNQUOTED, "foo \${bar baz" );
        $y = $x->substVariables( $rVariables );
        self::assertIsString( $y );
        self::assertStringContainsString( 'Unmatched', $y );
    }


    public function testSubstVariablesForBracesWithUndefinedVariable() : void {
        $rVariables = [ 'bar' => 'qux' ];
        $x = new JDWX\CLI\ParsedSegment( Segment::UNQUOTED, "foo \${baz}" );
        $y = $x->substVariables( $rVariables );
        self::assertIsString( $y );
        self::assertStringContainsString( 'Undefined', $y );
    }


    public function testSubstVariablesForBare() : void {
        $rVariables = [ 'bar' => 'qux' ];

        $x = new JDWX\CLI\ParsedSegment( Segment::UNQUOTED, "foo" );
        $y = $x->substVariables( [] );
        self::assertTrue( $y );
        self::assertSame( "foo", $x->getProcessed() );
        self::assertSame( "foo", $x->getOriginal() );

        $x = new JDWX\CLI\ParsedSegment( Segment::UNQUOTED, "foo" );
        $y = $x->substVariables( $rVariables );
        self::assertTrue( $y );
        self::assertSame( "foo", $x->getProcessed() );
        self::assertSame( "foo", $x->getOriginal() );

        $x = new JDWX\CLI\ParsedSegment( Segment::UNQUOTED, "foo \$bar baz" );
        $y = $x->substVariables( $rVariables );
        self::assertTrue( $y );
        self::assertSame( "foo qux baz", $x->getProcessed() );
        self::assertSame( "foo \$bar baz", $x->getOriginal() );
    }


    public function testSubstVariablesForBareWithUndefinedVariable() : void {
        $rVariables = [ 'bar' => 'qux' ];
        $x = new JDWX\CLI\ParsedSegment( Segment::UNQUOTED, "foo \$baz" );
        $y = $x->substVariables( $rVariables );
        self::assertIsString( $y );
        self::assertStringContainsString( 'Undefined', $y );
    }


    public function testSubstVariablesForBareValidAfterError() : void {
        $rVariables = [ 'bar' => 'qux' ];
        $x = new JDWX\CLI\ParsedSegment( Segment::UNQUOTED, "foo \$baz \$bar" );
        $y = $x->substVariables( $rVariables );
        # It's still an error.
        self::assertIsString( $y );
        self::assertStringContainsString( 'Undefined', $y );
    }


    public function testSubstVariablesForBareWithMultipleLonger() : void {
        $rVariables = [ 'foo' => 'qux', 'foobar' => 'quux' ];
        $x = new JDWX\CLI\ParsedSegment( Segment::UNQUOTED, "foo \$foobar baz" );
        $y = $x->substVariables( $rVariables );
        self::assertTrue( $y );
        self::assertSame( "foo quux baz", $x->getProcessed() );
        self::assertSame( "foo \$foobar baz", $x->getOriginal() );
    }


    public function testSubstVariablesForBareWithMultipleShorter() : void {
        $rVariables = [ 'foobar' => 'quux', 'foo' => 'qux' ];
        $x = new JDWX\CLI\ParsedSegment( Segment::UNQUOTED, "foo \$foobar baz" );
        $y = $x->substVariables( $rVariables );
        self::assertTrue( $y );
        self::assertSame( "foo quux baz", $x->getProcessed() );
        self::assertSame( "foo \$foobar baz", $x->getOriginal() );
    }


    public function testSubstVariablesForSingleQuotes() : void {
        $rVariables = [ 'bar' => 'qux' ];
        $x = new JDWX\CLI\ParsedSegment( Segment::SINGLE_QUOTED, "foo \$bar baz" );
        $y = $x->substVariables( $rVariables );
        self::assertTrue( $y );
        self::assertSame( "foo \$bar baz", $x->getProcessed() );
        self::assertSame( "'foo \$bar baz'", $x->getOriginal() );
    }


}
