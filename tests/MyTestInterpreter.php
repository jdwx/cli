<?php


declare( strict_types = 1 );


use JDWX\CLI\Interpreter;


class MyTestInterpreter extends Interpreter {


    public bool $yn = false;


    public function askYN( string $i_stPrompt ) : bool {
        return $this->yn;
    }


}
