<?php


declare( strict_types = 1 );


namespace JDWX\CLI;


use JDWX\Args\Arguments;
use JDWX\Quote\Operators\DelimiterOperator;
use JDWX\Quote\Operators\Escape\ControlCharEscape;
use JDWX\Quote\Operators\Escape\HexEscape;
use JDWX\Quote\Operators\MultiOperator;
use JDWX\Quote\Operators\OpenEndedOperator;
use JDWX\Quote\Operators\QuoteOperator;
use JDWX\Quote\Operators\RestOfLineOperator;
use JDWX\Quote\Parser;
use JDWX\Quote\ParserInterface;
use Psr\Log\LoggerInterface;


class Interpreter extends BaseInterpreter {


    /** @var array<string, string> */
    private array $rVariables = [];


    private bool $bUndefinedVariableIsError = true;


    public function __construct( string           $i_stPrompt = '> ', array|Arguments|null $i_argv = null,
                                 ?LoggerInterface $i_log = null, bool $i_bAddDefaultCommands = true ) {
        parent::__construct( $i_stPrompt, $i_argv, $i_log );
        if ( $i_bAddDefaultCommands ) {
            $this->addCommandDirectory( 'JDWX\\CLI\\Commands', __DIR__ . '/Commands' );
        }
    }


    public function getVariable( string $i_stName ) : ?string {
        if ( ! isset( $this->rVariables[ $i_stName ] ) ) {
            if ( $this->bUndefinedVariableIsError ) {
                $this->error( "Undefined variable: $i_stName" );
                return null;
            }
            return '';
        }
        return $this->rVariables[ $i_stName ];
    }


    public function getVariableString( string $i_stName ) : ?string {
        return $this->getVariable( $i_stName ) ?? '';
    }


    public function setVariable( string $i_stName, string $i_stValue ) : void {
        $this->rVariables[ $i_stName ] = $i_stValue;
    }


    /** @noinspection PhpMissingParentCallCommonInspection */
    protected function makeParser() : ParserInterface {
        return new Parser(
            comment: RestOfLineOperator::shComment(),
            hardQuote: QuoteOperator::single(),
            softQuote: QuoteOperator::double(),
            strongCallback: QuoteOperator::backtick(),
            weakCallback: QuoteOperator::varCurly(),
            openCallback: OpenEndedOperator::var(),
            escape: new MultiOperator( [ new HexEscape(), new ControlCharEscape() ] ),
            delimiter: DelimiterOperator::whitespace(),
            fnStrong: $this->substCommand( ... ),
            fnWeak: $this->getVariableString( ... ),
            fnOpen: $this->getVariableString( ... )
        );
    }


    protected function setUndefinedVariableIsError( bool $i_bValue ) : void {
        $this->bUndefinedVariableIsError = $i_bValue;
    }


}
