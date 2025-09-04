<?php


declare( strict_types = 1 );


namespace JDWX\CLI;


use JDWX\Strict\OK;


final class CommandMatcher {


    /**
     * @param list<string> $i_rInput
     * @param list<string> $i_rCommands
     * @return list<string>
     */
    public static function match( array $i_rInput, array $i_rCommands ) : array {
        $rMatches = $i_rCommands;
        foreach ( $i_rInput as $ii => $argInput ) {
            $rNewMatches = [];
            foreach ( $rMatches as $stCommand ) {
                $rCommand = OK::preg_split_list( '/\s+/', $stCommand );
                if ( $ii >= count( $rCommand ) ) {
                    $rNewMatches[] = $stCommand;
                    continue;
                }
                $argCommand = $rCommand[ $ii ];
                if ( str_starts_with( $argCommand, $argInput ) ) {
                    $rNewMatches[] = $stCommand;
                }
            }
            $rMatches = $rNewMatches;
        }
        return $rMatches;
    }


    /**
     * Winnow gets called when there are multiple matches because it can try to find longer
     * matches. For example, if the input is "foo bar baz" and the commands are "foo" and "foo bar",
     * then "foo bar" is a better match than "foo".
     *
     * Winnow can't help you with partial matches of commands that are the same length. For example,
     * if the input is "foo ba" and the commands are "foo bar" and "foo baz", then you're out of luck.
     *
     * @param string[] $i_rInput
     * @param string[] $i_rCommands
     * @return list<string>
     */
    public static function winnow( array $i_rInput, array $i_rCommands ) : array {
        $rMatches = $i_rCommands;
        $rNewMatches = [];
        $uMaxMatchLen = 0;
        foreach ( $rMatches as $stCommand ) {
            $rCommand = OK::preg_split_list( '/\s+/', $stCommand );
            $uMatchLen = self::winnowScore( $i_rInput, $rCommand );
            if ( $uMatchLen < $uMaxMatchLen ) {
                continue;
            }
            if ( $uMatchLen > $uMaxMatchLen ) {
                $uMaxMatchLen = $uMatchLen;
                $rNewMatches = [];
            }
            $rNewMatches[] = $stCommand;
        }
        return $rNewMatches;
    }


    /**
     * @param string[] $i_rInput
     * @param string[] $i_rCommand
     * @return int
     */
    public static function winnowScore( array $i_rInput, array $i_rCommand ) : int {
        $uInputLen = count( $i_rInput );
        $uCommandLen = count( $i_rCommand );
        $uMatchLen = 0;

        for ( $ii = 0 ; $ii < $uInputLen ; $ii++ ) {
            if ( $ii >= $uCommandLen ) {
                # If the command is shorter than the input, we're done.
                return $uMatchLen;
            }
            if ( ! str_starts_with( $i_rCommand[ $ii ], $i_rInput[ $ii ] ) ) {
                # If the command doesn't start with the input, it can't be right.
                return 0;
            }
            if ( $i_rInput[ $ii ] === $i_rCommand[ $ii ] ) {
                # If this component of the input and command are identical, award an extra point.
                $uMatchLen += 1;
            }
            # Add 10 points for each component of the command that matches the input.
            $uMatchLen += 10;
        }

        # If the input and the command are the same length, we give that a huge
        # boost because a command that matches the length exactly should always
        # win out over any inexact matches.
        if ( $uInputLen === $uCommandLen ) {
            $uMatchLen += 1_000_000;
        }
        return $uMatchLen;
    }


}
