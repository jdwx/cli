<?php


namespace JDWX\CLI;


use JDWX\Args\Arguments;
use JDWX\Args\ParsedString;
use JDWX\CLI\Commands\CommandEcho;
use JDWX\CLI\Commands\CommandExit;
use JDWX\CLI\Commands\CommandExpr;
use JDWX\CLI\Commands\CommandHelp;
use JDWX\CLI\Commands\CommandHistory;
use JDWX\CLI\Commands\CommandHistoryRun;
use JDWX\CLI\Commands\CommandHistorySearch;
use JDWX\CLI\Commands\CommandSet;
use Psr\Log\LoggerInterface;


class Interpreter extends BaseInterpreter {


    /** @var array<string, string> */
    private array $rVariables = [];


    public function __construct( string           $i_stPrompt = '> ', array|Arguments|null $i_argv = null,
                                 ?LoggerInterface $i_log = null ) {
        parent::__construct( $i_stPrompt, $i_argv, $i_log );
        $this->addCommandClass( CommandEcho::class );
        $this->addCommandClass( CommandExit::class );
        $this->addCommandClass( CommandExpr::class );
        $this->addCommandClass( CommandHelp::class );
        $this->addCommandClass( CommandHistory::class );
        $this->addCommandClass( CommandHistoryRun::class );
        $this->addCommandClass( CommandHistorySearch::class );
        $this->addCommandClass( CommandSet::class );
        $fn = function ( string $i_stText, int $i_nIndex ) : array {
            return $this->readlineCompletion( $i_stText, $i_nIndex );
        };
        readline_completion_function( $fn );
    }


    public function getVariable( string $i_stName ) : string {
        return $this->rVariables[ $i_stName ];
    }


    public function setVariable( string $i_stName, string $i_stValue ) : void {
        $this->rVariables[ $i_stName ] = $i_stValue;
    }


    protected function subst( ParsedString $i_rInput ) : bool {
        $bst = $i_rInput->substVariables( $this->rVariables );
        if ( is_string( $bst ) ) {
            $this->error( $bst, [
                'input' => $i_rInput->debug(),
                'vars' => $this->rVariables,
            ] );
            return false;
        }
        return parent::subst( $i_rInput );
    }


}
