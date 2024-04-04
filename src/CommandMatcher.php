<?php


declare( strict_types = 1 );


namespace JDWX\CLI;


final class CommandMatcher {


    public static function match( array $i_rInput, array $i_rCommands ) : array {
        $rMatches = $i_rCommands;
        foreach ( $i_rInput as $ii => $argInput ) {
            $rNewMatches = [];
            foreach ( $rMatches as $stCommand ) {
                $rCommand = preg_split( '/\s+/', $stCommand );
                if ( $ii >= count( $rCommand ) ) {
                    // echo $ii, ". ", $stCommand, " <=> ", $argInput, " (length)\n";
                    $rNewMatches[] = $stCommand;
                    continue;
                }
                $argCommand = $rCommand[ $ii ];
                if ( str_starts_with( $argCommand, $argInput ) ) {
                    // echo $ii, ". ", $argCommand, " <=> ", $argInput, " (match)\n";
                    $rNewMatches[] = $stCommand;
                    // continue; # not needed when echo below is commented out
                }
                // echo $ii, ". ", $argCommand, " <=> ", $argInput, " (XXX)\n";
            }
            $rMatches = $rNewMatches;
        }
        return $rMatches;
    }


    public static function winnow( array $i_rInput, array $i_rCommands ) : array {
        $argc = count( $i_rInput );
        $rMatches = $i_rCommands;
        $rNewMatches = [];
        $uMaxMatchLen = 0;
        foreach ( $rMatches as $stCommand ) {
            $rCommand = preg_split( '/\s+/', $stCommand );
            if ( count ( $rCommand ) == $argc ) {
                # An exact match should always win over a longer inexact match.
                $uMatchLen = 1_000_000;
            } else {
                # Otherwise, the more components of the command that match the input, the better.
                $uMatchLen = min( count( $rCommand ), $argc );
            }
            if ( $uMatchLen < $uMaxMatchLen ) continue;
            if ( $uMatchLen > $uMaxMatchLen ) {
                $uMaxMatchLen = $uMatchLen;
                $rNewMatches = [];
            }
            $rNewMatches[] = $stCommand;
            // echo "Match: ", $stCommand, " (", $uMatchLen, ")\n";
        }
        return $rNewMatches;
    }





}
