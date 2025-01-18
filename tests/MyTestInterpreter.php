<?php


declare( strict_types = 1 );


use JDWX\Args\Arguments;
use JDWX\CLI\Interpreter;
use Psr\Log\LoggerInterface;


require_once __DIR__ . '/MyMultiwordTestCommand.php';


class MyTestInterpreter extends Interpreter {


    public bool $yn = false;
    public string $lineBuffer = 'line_buffer';
    public int $end = 3;
    public ?int $status = null;
    public ?Exception $ex = null;
    public array $readLines = [];


    public function __construct( string $i_stPrompt = '> ', array|Arguments|null $i_argv = null, ?LoggerInterface $i_log = null ) {
        if ( is_null( $i_argv ) ) {
            $i_argv = new Arguments( [ 'test/command' ] );
        }
        parent::__construct( $i_stPrompt, $i_argv, $i_log );
        $this->addCommandClass( MyMultiwordTestCommand::class );
    }


    public function askYN( string $i_stPrompt, ?bool $i_nbDefault = null, bool $i_bReturnOnFail = false ) : bool {
        return $this->yn;
    }


    protected function exit( int $i_iStatus ) : void {
        $this->status = $i_iStatus;
    }


    protected function handleException( Exception $i_ex ) : ?int {
        $this->ex = $i_ex;
        return null;
    }


    protected function readLine( ?string $i_nstPrompt = null ) : false|string {
        if ( empty( $this->readLines ) ) {
            return false;
        }
        return array_shift( $this->readLines );
    }


    protected function readlineInfo() : array {
        return [
            'line_buffer' => $this->lineBuffer,
            'end' => $this->end,
        ];
    }


}
