<?php


declare( strict_types = 1 );


use JDWX\CLI\LineParser;
use JDWX\CLI\ParsedLine;
use PHPUnit\Framework\TestCase;


class LineParserTest extends TestCase {


    public function testParseLineForBackQuotes() : void {
        $x = LineParser::parseLine( '`foo`' );
        self::assertInstanceOf( ParsedLine::class, $x );
        self::assertCount( 1, $x );
        self::assertSame( 'foo', $x->getSegment( 0 )->getProcessed() );
    }


    public function testParseLineForBackQuotesWithEscapedBackQuote() : void {
        $x = LineParser::parseLine( '`foo\\`bar`' );
        self::assertInstanceOf( ParsedLine::class, $x );
        self::assertCount( 1, $x );
        self::assertSame( 'foo`bar', $x->getSegment( 0 )->getProcessed() );
    }


    public function testParseLineForBackQuotesWithMissingEndQuote() : void {
        $x = LineParser::parseLine( '`foo' );
        self::assertIsString( $x );
        self::assertStringContainsString( 'Unmatched', $x );
    }


    public function testParseLineForBackslashAsLastCharacter() : void {
        $x = LineParser::parseLine( 'foo\\' );
        self::assertIsString( $x );
        self::assertStringContainsString( 'Hanging', $x );
    }


    public function testParseLineForBackslashUnicode() : void {
        $x = LineParser::parseLine( 'foo\\u00C3bar' );
        self::assertInstanceOf( ParsedLine::class, $x );
        self::assertCount( 3, $x );
        self::assertSame( '\\u00C3', $x->getSegment( 1 )->getOriginal() );
        self::assertSame( 'Ã', $x->getSegment( 1 )->getProcessed() );
    }


    public function testParseLineForBackslashOctal() : void {
        $x = LineParser::parseLine( 'foo\\101bar' );
        self::assertInstanceOf( ParsedLine::class, $x );
        self::assertCount( 3, $x );
        self::assertSame( '\\101', $x->getSegment( 1 )->getOriginal() );
        self::assertSame( 'A', $x->getSegment( 1 )->getProcessed() );
    }


    public function testParseLineForBackslashNewline() : void {
        $x = LineParser::parseLine( "foo\\nbar" );
        self::assertInstanceOf( ParsedLine::class, $x );
        self::assertCount( 3, $x );
        self::assertSame( '\\n', $x->getSegment( 1 )->getOriginal() );
        self::assertSame( "\n", $x->getSegment( 1 )->getProcessed() );
    }


    public function testParseLineForCommentPartialLine() : void {
        $x = LineParser::parseLine( 'foo # bar' );
        self::assertInstanceOf( ParsedLine::class, $x );
        self::assertCount( 3, $x );
        self::assertSame( 'foo', $x->getSegment( 0 )->getProcessed() );
        self::assertSame( ' ', $x->getSegment( 1 )->getProcessed() );
        self::assertSame( '', $x->getSegment( 2 )->getProcessed() );
    }


    public function testParseLineForCommentInQuotes() : void {
        $x = LineParser::parseLine( '"foo # bar"' );
        self::assertInstanceOf( ParsedLine::class, $x );
        self::assertCount( 1, $x );
        self::assertSame( 'foo # bar', $x->getSegment( 0 )->getProcessed() );
    }


    public function testParseLineForCommentWholeLine() : void {
        $x = LineParser::parseLine( '# foo' );
        self::assertInstanceOf( ParsedLine::class, $x );
        self::assertCount( 1, $x );
        self::assertSame( '', $x->getSegment( 0 )->getProcessed() );
    }


    public function testParseLineForEmpty() : void {
        $x = LineParser::parseLine( '' );
        self::assertInstanceOf( ParsedLine::class, $x );
        self::assertCount( 0, $x );
    }


    public function testParseLineForSingleWord() : void {
        $x = LineParser::parseLine( 'foo' );
        self::assertInstanceOf( ParsedLine::class, $x );
        self::assertCount( 1, $x );
        self::assertSame( 'foo', $x->getSegment( 0 )->getProcessed() );
    }


    public function testParseLineForDoubleQuotedWord() : void {
        $x = LineParser::parseLine( '"foo"' );
        self::assertInstanceOf( ParsedLine::class, $x );
        self::assertCount( 1, $x );
        self::assertSame( 'foo', $x->getSegment( 0 )->getProcessed() );
    }


    public function testParseLineForDoubleQuotedWords() : void {
        $x = LineParser::parseLine( '"foo bar"' );
        self::assertInstanceOf( ParsedLine::class, $x );
        self::assertCount( 1, $x );
        self::assertSame( 'foo bar', $x->getSegment( 0 )->getProcessed() );
    }


    public function testParseLineForDoubleQuotedWordWithEscapedQuote() : void {
        $x = LineParser::parseLine( '"foo\""' );
        self::assertInstanceOf( ParsedLine::class, $x );
        self::assertCount( 1, $x );
        self::assertSame( 'foo"', $x->getSegment( 0 )->getProcessed() );
    }


    public function testParseLineForDoubleQuoteMissingEndQuote() : void {
        $x = LineParser::parseLine( 'foo "bar' );
        self::assertIsString( $x );
        self::assertStringContainsString( 'Unmatched', $x );
    }


    public function testParseLineForSingleQuoteMissingEndQuote() : void {
        $x = LineParser::parseLine( "foo 'bar" );
        self::assertIsString( $x );
        self::assertStringContainsString( 'Unmatched', $x );
    }


    public function testParseLineForSingleQuotedWord() : void {
        $x = LineParser::parseLine( "'foo'" );
        self::assertInstanceOf( ParsedLine::class, $x );
        self::assertCount( 1, $x );
        self::assertSame( 'foo', $x->getSegment( 0 )->getProcessed() );
    }


    public function testParseLineForSingleQuotedWords() : void {
        $x = LineParser::parseLine( "'foo bar'" );
        self::assertInstanceOf( ParsedLine::class, $x );
        self::assertCount( 1, $x );
        self::assertSame( 'foo bar', $x->getSegment( 0 )->getProcessed() );
    }


    public function testParseLineForSingleQuotedWordWithEscapedQuote() : void {
        $x = LineParser::parseLine( "'foo\\' bar'" );
        self::assertInstanceOf( ParsedLine::class, $x );
        self::assertCount( 1, $x );
        self::assertSame( "foo' bar", $x->getSegment( 0 )->getProcessed() );
    }


    public function testParseLineForSingleQuotesWithEscapedBackslash() : void {
        $x = LineParser::parseLine( '\'foo\\ bar\'' );
        self::assertInstanceOf( ParsedLine::class, $x );
        self::assertCount( 1, $x );
        self::assertSame( 'foo\\ bar', $x->getSegment( 0 )->getProcessed() );
    }


    public function testParseLineForSingleQuoteWithEscapeSequence() : void {
        $x = LineParser::parseLine( "'foo\\n bar'" );
        self::assertInstanceOf( ParsedLine::class, $x );
        self::assertCount( 1, $x );
        self::assertSame( 'foo\\n bar', $x->getSegment( 0 )->getProcessed() );
    }



    public function testParseQuoteForDoubleQuote() : void {
        $x = LineParser::parseQuote( 'foo" bar', '"' );
        self::assertIsArray( $x );
        self::assertCount( 2, $x );
        self::assertSame( 'foo', $x[0] );
        self::assertSame( ' bar', $x[1] );
    }


    public function testParseQuoteForSingleQuote() : void {
        $x = LineParser::parseQuote( "foo' bar", "'" );
        self::assertIsArray( $x );
        self::assertCount( 2, $x );
        self::assertSame( 'foo', $x[0] );
        self::assertSame( ' bar', $x[1] );
    }


    public function testParseQuoteForDoubleQuoteWithEscapedQuote() : void {
        $x = LineParser::parseQuote( 'foo\" bar" baz', '"' );
        self::assertIsArray( $x );
        self::assertCount( 2, $x );
        self::assertSame( 'foo" bar', $x[0] );
        self::assertSame( ' baz', $x[1] );
    }


    public function testParseQuoteForSingleQuoteWithEscapedQuote() : void {
        $x = LineParser::parseQuote( "foo\\' bar' baz", "'" );
        self::assertIsArray( $x );
        self::assertCount( 2, $x );
        self::assertSame( "foo' bar", $x[0] );
        self::assertSame( ' baz', $x[1] );
    }


    public function testParseQuoteForEscapedBackslash() : void {
        $x = LineParser::parseQuote( 'foo\\ bar" baz', '"' );
        self::assertIsArray( $x );
        self::assertCount( 2, $x );
        self::assertSame( 'foo\\ bar', $x[0] );
        self::assertSame( ' baz', $x[1] );
    }


    public function testParseQuoteForUnterminatedQuote() : void {
        $x = LineParser::parseQuote( 'foo bar', "'" );
        self::assertIsString( $x );
        self::assertStringContainsString( 'Unmatched', $x );
    }


}
