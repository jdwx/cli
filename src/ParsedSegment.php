<?php


declare( strict_types = 1 );


namespace JDWX\CLI;


class ParsedSegment {


    public Segment $type;

    public string $text;


    public function __construct( Segment $i_type, string $i_text ) {
        $this->type = $i_type;
        $this->text = $i_text;
    }


    public function getOriginal() : string {
        return match ( $this->type ) {
            Segment::DELIMITER, Segment::UNQUOTED => $this->text,
            Segment::SINGLE_QUOTED => "'" . $this->text . "'",
            Segment::DOUBLE_QUOTED => '"' . $this->text . '"',
            Segment::BACK_QUOTED => "`" . $this->text . "`",
        };
    }


    public function isDelimiter() : bool {
        return Segment::DELIMITER === $this->type;
    }


    public function substBackQuotes( BaseInterpreter $i_cli ) : void {
        if ( Segment::BACK_QUOTED !== $this->type ) {
            return;
        }
        ob_start();
        $i_cli->handleCommand( $this->text );
        $this->text = trim( ob_get_clean() );
    }


    public function substEscapeSequences() : void {
        if ( Segment::SINGLE_QUOTED === $this->type ) {
            return;
        }
        $this->text = str_replace( "\\n", "\n", $this->text );
        $this->text = str_replace( "\\r", "\r", $this->text );
        $this->text = str_replace( "\\t", "\t", $this->text );
        $this->text = str_replace( "\\v", "\v", $this->text );
        $this->text = str_replace( "\\e", "\e", $this->text );
        $this->text = str_replace( "\\f", "\f", $this->text );
        $this->text = str_replace( "\\a", "\a", $this->text );
        $this->text = str_replace( "\\b", "\b", $this->text );
        $this->text = str_replace( "\\0", "\0", $this->text );
    }


    public function substVariables( array $i_rVariables ) : true|string {
        if ( Segment::SINGLE_QUOTED === $this->type ) {
            return true;
        }
        $bst = $this->substVariablesWithBrackets( $i_rVariables );
        if ( true !== $bst ) {
            return $bst;
        }
        return $this->substVariablesBare( $i_rVariables );
    }


    private function substVariablesBare( array $i_rVariables ) : true|string {
        $bst = true;
        $st = preg_replace_callback( '/\$([a-zA-Z_][a-zA-Z0-9_]*)/', function ( $matches ) use ( &$i_rVariables, &$bst ) {
            if ( true !== $bst ) {
                return "";
            }
            $stVar = $matches[ 1 ];
            $uMaxMatch = 0;
            $stSubst = "";
            foreach ( $i_rVariables as $key => $value ) {
                if ( ! str_starts_with( $stVar, $key ) ) {
                    continue;
                }
                if ( strlen( $key ) <= $uMaxMatch ) {
                    continue;
                }
                $stSubst = $value;
                $uMaxMatch = strlen( $key );
            }
            if ( $uMaxMatch > 0 ) {
                return $stSubst . substr( $stVar, $uMaxMatch );
            }
            $bst = "Undefined variable: $stVar";
            return $stVar;
        }, $this->text );
        if ( $bst !== true ) {
            return $bst;
        }
        $this->text = $st;
        return true;
    }


    private function substVariablesWithBrackets( array $i_rVariables ) : true|string {
        $bst = true;
        $st = preg_replace_callback( '/\$\{([a-zA-Z_][a-zA-Z0-9_]*)}/', function ( $matches ) use ( &$i_rVariables, &$bst ) {
            $stVar = $matches[ 1 ];
            if ( array_key_exists( $stVar, $i_rVariables ) ) {
                return $i_rVariables[ $stVar ];
            }
            if ( true === $bst ) {
                $bst = "Undefined variable: $stVar";
            }
            return $stVar;
        }, $this->text );

        if ( is_string( $bst ) ) {
            return $bst;
        }

        $this->text = $st;
        return true;
    }


}
