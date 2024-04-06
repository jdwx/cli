<?php


declare( strict_types = 1 );


namespace JDWX\CLI;


require_once __DIR__ . "/ParsedSegment.php";


class ParsedLine {


    /** @var ParsedSegment[] */
    private array $rSegments = [];


    public function addBackQuoted( string $i_st ) : void {
        $this->addSegment( Segment::BACK_QUOTED, $i_st );
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


    /** @return string[] */
    public function getSegments() : array {
        $st = "";
        $rOut = [];
        foreach ( $this->rSegments as $seg ) {
            if ( $seg->isDelimiter() ) {
                if ( $st ) {
                    $rOut[] = $st;
                    $st = "";
                }
                continue;
            }
            $st .= $seg->text;
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


    public function substEscapeSequences() : void {
        foreach ( $this->rSegments as $seg ) {
            $seg->substEscapeSequences();
        }
    }


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
