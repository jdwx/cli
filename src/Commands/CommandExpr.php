<?php


declare( strict_types = 1 );


namespace JDWX\CLI\Commands;


use JDWX\Args\Arguments;
use JDWX\Args\BadArgumentException;
use JDWX\CLI\Command;


class CommandExpr extends Command {


    protected const string COMMAND = 'expr';

    protected const string HELP    = 'Evaluate a simple expression.';

    protected const string USAGE   = 'expr <number> <operator> <number>';


    protected function run( Arguments $args ) : void {
        $f1 = $args->shiftFloatEx();
        $rOperators = [ '+', '-', '*', '/' ];
        $op = $args->shiftKeywordEx( $rOperators );
        $f2 = $args->shiftFloatEx();
        $fResult = match ( $op ) {
            '+' => $f1 + $f2,
            '-' => $f1 - $f2,
            '*' => $f1 * $f2,
            '/' => $f1 / $f2,
            default => throw new BadArgumentException( $op, 'Invalid operator.' ),
        };
        echo $fResult . "\n";
    }


}
