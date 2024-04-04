<?php


declare( strict_types = 1 );


namespace JDWX\CLI;


use JDWX\Args\Arguments;
use LogicException;


abstract class Command {


    protected const COMMAND = "____OVERLOAD_ME____";
    protected const ALIASES = [];
    protected const HELP = null;
    protected const USAGE = null;
    protected const OPTIONS = [
        /** If options are used they are of the form "key" => "default_value". */
    ];


    private Interpreter $cli;
    private ?array $nrOptions = null;


    public function __construct( Interpreter $i_cli ) {
        $this->cli = $i_cli;
    }


    public function askYN( string $i_stPrompt ) : bool {
        return $this->cli()->askYN( $i_stPrompt );
    }


    protected function checkOption( string $i_stOption, bool|string $i_bstFlag ) : bool {
        if ( ! array_key_exists( $i_stOption, static::OPTIONS ) ) {
            throw new LogicException( "Option {$i_stOption} checked not defined." );
        }
        if ( is_null( $this->nrOptions ) ) {
            throw new LogicException( "Options checked but not yet handled." );
        }
        return ( $this->nrOptions[ $i_stOption ] ?? static::OPTIONS[ $i_stOption ] ) === $i_bstFlag;
    }


    public function getAliases() : array {
        if ( is_array( static::ALIASES ) ) {
            return static::ALIASES;
        } elseif ( is_null( static::ALIASES ) ) {
            return [];
        } elseif ( is_string( static::ALIASES ) ) {
            return [ static::ALIASES ];
        }
        throw new LogicException( "ALIASES must be array, string, or null." );
    }


    public function getCommand() : string {
        return static::COMMAND;
    }


    public function getHelp() : ?string {
        return static::HELP;
    }


    public function getUsage() : ?string {
        return static::USAGE;
    }


    /**
     * This arguably should be called automatically, but some existing commands that
     * use this code expect that it hasn't been. This may change in the future after
     * we are able to find and update those.
     */
    protected function handleOptions( Arguments $io_args ) : void {
        $this->nrOptions = $io_args->handleOptionsDefined( array_keys( static::OPTIONS ) );
    }


    abstract public function run( Arguments $args ) : void;


    protected function cli() : Interpreter {
        return $this->cli;
    }


}
