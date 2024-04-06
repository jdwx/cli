<?php


declare( strict_types = 1 );


namespace JDWX\CLI;


class ParsedSegment {


    protected Segment $type;

    protected string $textProcessed;
    protected string $textOriginal;


    public function __construct( Segment $i_type, string $i_text ) {
        $this->type = $i_type;
        $this->textProcessed = $i_text;
        $this->textOriginal = $i_text;
    }


    public function getOriginal( bool $i_bIncludeComments = false ) : string {
        return match ( $this->type ) {
            Segment::DELIMITER, Segment::UNQUOTED => $this->textOriginal,
            Segment::SINGLE_QUOTED => "'" . $this->textOriginal . "'",
            Segment::DOUBLE_QUOTED => '"' . $this->textOriginal . '"',
            Segment::BACK_QUOTED => "`" . $this->textOriginal . "`",
            Segment::COMMENT => $i_bIncludeComments ? "#" . $this->textOriginal : "",
        };
    }


    public function getProcessed() : string {
        return match ( $this->type ) {
            Segment::DELIMITER, Segment::SINGLE_QUOTED, Segment::BACK_QUOTED => $this->textProcessed,
            Segment::UNQUOTED, Segment::DOUBLE_QUOTED => self::substEscapeSequences( $this->textProcessed ),
            Segment::COMMENT => "",
        };
    }


    public function isComment() : bool {
        return Segment::COMMENT === $this->type;
    }


    public function isDelimiter() : bool {
        return Segment::DELIMITER === $this->type;
    }


    public function substBackQuotes( BaseInterpreter $i_cli ) : void {
        if ( Segment::BACK_QUOTED !== $this->type ) {
            return;
        }
        ob_start();
        $i_cli->handleCommand( $this->textProcessed );
        $this->textProcessed = trim( ob_get_clean() );
    }


    public static function substEscapeSequences( string $st ) : string {

        # Handle special character escape sequences.
        $st = str_replace( "\\n", "\n", $st );
        $st = str_replace( "\\r", "\r", $st );
        $st = str_replace( "\\t", "\t", $st );
        $st = str_replace( "\\v", "\v", $st );
        $st = str_replace( "\\e", "\e", $st );
        $st = str_replace( "\\f", "\f", $st );
        $st = str_replace( "\\a", "\a", $st );
        $st = str_replace( "\\b", "\b", $st );
        $st = str_replace( "\\0", "\0", $st );

        # Handle octal escape sequences.
        $st = preg_replace_callback( '/\\\\([0-7]{1,3})/', function ( $matches ) {
            return chr( octdec( $matches[ 1 ] ) );
        }, $st );

        # Handle Unicode escape sequences like "\u00C3" => "Ã".
        $st = preg_replace_callback( '/\\\\[uU]([0-9a-fA-F]{4})/', function ( $matches ) {
            return mb_convert_encoding( pack( 'H*', $matches[ 1 ] ), 'UTF-8', 'UCS-2BE' );
        }, $st );

        # Anything else, just remove the backslash as if unquoted.
        return preg_replace( '/\\\\(.)/', '$1', $st );

    }


    public function substVariables( array $i_rVariables ) : true|string {
        if ( Segment::SINGLE_QUOTED === $this->type ) {
            return true;
        }
        $bst = $this->substVariablesWithBraces( $i_rVariables );
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
        }, $this->textProcessed );
        if ( $bst !== true ) {
            return $bst;
        }
        $this->textProcessed = $st;
        return true;
    }


    private function substVariablesWithBraces( array $i_rVariables ) : true|string {
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
        }, $this->textProcessed );

        if ( is_string( $bst ) ) {
            return $bst;
        }

        $matches = [];
        preg_match( '/\$\{([a-zA-Z_][a-zA-Z0-9_]*)/', $st, $matches );
        if ( count( $matches ) > 0 ) {
            return "Unmatched brace in variable substitution";
        }

        $this->textProcessed = $st;
        return true;
    }


}
