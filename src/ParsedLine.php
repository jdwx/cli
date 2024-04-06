<?php


declare( strict_types = 1 );


namespace JDWX\CLI;


use Countable;


require_once __DIR__ . "/ParsedSegment.php";


class ParsedLine implements Countable {


    /** @var ParsedSegment[] */
    private array $rSegments = [];


    public function addBackQuoted( string $i_st ) : void {
        $this->addSegment( Segment::BACK_QUOTED, $i_st );
    }


    public function addComment( string $i_st ) : void {
        $this->addSegment( Segment::COMMENT, $i_st );
    }


    public function addDoubleQuoted( string $i_st ) : void {
        $this->addSegment( Segment::DOUBLE_QUOTED, $i_st );
    }


    public function addSingleQuoted( string $i_st ) : void {
        $this->addSegment( Segment::SINGLE_QUOTED, $i_st );
    }


    public function addSpace( $i_ch = " " ) : void {
        $this->addSegment( Segment::DELIMITER, $i_ch );
    }


    public function addUnquoted( string $i_st ) : void {
        $this->addSegment( Segment::UNQUOTED, $i_st );
    }


    public function count() : int {
        return count( $this->rSegments );
    }


    /**
     * Reconstructs the originally-entered text as closely as possible.
     *
     * @param int $i_uSkipArgs The number of arguments to skip. This is useful
     *                         when you want to get the original text of a line
     *                         without the command name, for example.
     * @return string The original text of the line. (Approximately.)
     */
    public function getOriginal( int $i_uSkipArgs = 0 ) : string {
        $rOut = [];
        foreach ( $this->rSegments as $seg ) {
            if ( $i_uSkipArgs > 0 && $seg->isDelimiter() ) {
                $i_uSkipArgs--;
                continue;
            }
            if ( $i_uSkipArgs > 0 ) {
                continue;
            }
            $rOut[] = $seg->getOriginal();
        }
        return implode( "", $rOut );
    }


    /**
     * @return string The fully-processed text of the line after all
     *                substitutions and quotes have been resolved.
     *
     * See also: getSegments() which is more useful most of the time.
     * This method is mainly helpful for debugging and testing.
     */
    public function getProcessed() : string {
        $st = "";
        foreach ( $this->rSegments as $seg ) {
            $st .= $seg->getProcessed();
        }
        return $st;
    }


    /**
     * @param int $i_uIndex Which segment to return.
     * @return ParsedSegment The requested segment.
     *
     * This is mainly useful for testing.
     */
    public function getSegment( int $i_uIndex ) : ParsedSegment {
        return $this->rSegments[ $i_uIndex ];
    }


    /**
     * Returns the line as an array of individual arguments after quotes and
     * substitutions have been performed. So all the weird cases like foo" bar"
     * (which is one argument 'foo bar') are resolved at this point.
     *
     * @return string[] An array of individual arguments.
     */
    public function getSegments() : array {
        $st = "";
        $rOut = [];
        foreach ( $this->rSegments as $seg ) {
            if ( $seg->isDelimiter() ) {
                if ( $st !== "" ) {
                    $rOut[] = $st;
                    $st = "";
                }
                continue;
            }
            $st .= $seg->getProcessed();
        }
        if ( $st !== "" ) {
            $rOut[] = $st;
        }
        return $rOut;
    }


    public function substBackQuotes( BaseInterpreter $i_cli ) : void {
        foreach ( $this->rSegments as $seg ) {
            $seg->substBackQuotes( $i_cli );
        }
    }


    /**
     * If this method returns an error, the underlying segments are in an
     * inconsistent state and should not be used.
     *
     * @return true|string True if successful, otherwise an error message.
     */
    public function substVariables( array $i_rVariables ) : true|string {
        foreach ( $this->rSegments as $seg ) {
            $bst = $seg->substVariables( $i_rVariables );
            if ( is_string( $bst ) ) {
                return $bst;
            }
        }
        return true;
    }


    private function addSegment( Segment $i_type, string $i_text ) : void {
        if ( "" === $i_text ) {
            return;
        }
        $this->rSegments[] = new ParsedSegment( $i_type, $i_text );
    }


}
