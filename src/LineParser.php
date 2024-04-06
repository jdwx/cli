<?php


declare( strict_types = 1 );


namespace JDWX\CLI;


final class LineParser {


    public static function parseQuote( string $i_st, string $i_stQuoteCharacter ) : array|string {
        $stOut = "";
        $stRest = $i_st;
        while ( true ) {
            $iSpan = strpos( $stRest, $i_stQuoteCharacter );
            if ( false === $iSpan ) {
                return "Unmatched {$i_stQuoteCharacter}.";
            }
            if ( $iSpan > 0 && substr( $stRest, $iSpan - 1, 1 ) === "\\" ) {
                $stOut .= substr( $stRest, 0, $iSpan - 1 ) . $i_stQuoteCharacter;
                $stRest = substr( $stRest, $iSpan + 1 );
                continue;
            }
            $stOut .= substr( $stRest, 0, $iSpan );
            $stRest = substr( $stRest, $iSpan + 1 );
            return [ $stOut, $stRest ];
        }
    }


    public static function parseLine( string $i_stLine ) : ParsedLine|string {
        $st = trim( preg_replace( "/\s\s+/", " ", $i_stLine ) );
        $pln = new ParsedLine();
        while ( $st !== "" ) {
            $iSpan = strcspn( $st, " \\\"'#`" );
            $stUnquoted = substr( $st, 0, $iSpan );
            $pln->addUnquoted( $stUnquoted );
            $ch = substr( $st, $iSpan, 1 );
            $stRest = substr( $st, $iSpan + 1 );
            // echo $stSpan, "|", $ch, "|", $stRest, "\n";
            if ( ' ' === $ch || '' === $ch ) {
                $pln->addSpace();
            } elseif ( '"' == $ch ) {
                $r = self::parseQuote( $stRest, '"' );
                if ( is_string( $r ) ) {
                    return $r;
                }
                $pln->addDoubleQuoted( $r[ 0 ] );
                $stRest = $r[ 1 ];
            } elseif ( "'" == $ch ) {
                $r = self::parseQuote( $stRest, '\'' );
                if ( is_string( $r ) ) {
                    return $r;
                }
                $pln->addSingleQuoted( $r[ 0 ] );
                $stRest = $r[ 1 ];
            } elseif ( "`" === $ch ) {
                $r = self::parseQuote( $stRest, '`' );
                if ( is_string( $r ) ) {
                    return $r;
                }
                $pln->addBackQuoted( $r[ 0 ] );
                $stRest = $r[ 1 ];
            } elseif ( "#" === $ch ) {
                $pln->addComment( $stRest );
                break;
            } elseif ( "\\" === $ch ) {
                if ( "" === $stRest ) {
                    return "Unmatched backslash.";
                }
                if ( preg_match( '/[uU][0-9a-fA-F]{4}/', $stRest ) ) {
                    $stNext = substr( $stRest, 0, 5 );
                    $stRest = substr( $stRest, 5 );
                } elseif ( preg_match( '/[0-7]{1,3}/', $stRest ) ) {
                    $stNext = substr( $stRest, 0, 3 );
                    $stRest = substr( $stRest, 3 );
                } else {
                    $stNext = substr( $stRest, 0, 1 );
                    $stRest = substr( $stRest, 1 );
                }
                $pln->addUnquoted( '\\' . $stNext );
            } else {
                return "Unexpected character: $ch";
            }
            $st = $stRest;
        }
        return $pln;

    }


}
