<?php


declare( strict_types = 1 );


namespace JDWX\CLI\Tests;


use JDWX\Args\Arguments;
use JDWX\CLI\AbstractCommand;
use JDWX\CLI\Command;
use JDWX\Log\BufferLogger;
use JDWX\Strict\OK;
use LogicException;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Psr\Log\LogLevel;
use RuntimeException;
use Stringable;


require_once __DIR__ . '/MyTestCommand.php';
require_once __DIR__ . '/MyTestInterpreter.php';


#[CoversClass( AbstractCommand::class )]
#[CoversClass( Command::class )]
final class CommandTest extends TestCase {


    public function testAskYN() : void {
        $cli = new MyTestInterpreter();
        $command = new MyTestCommand( $cli );
        $cli->yn = true;
        self::assertTrue( $command->askYN( 'foo' ) );
        $cli->yn = false;
        self::assertFalse( $command->askYN( 'foo' ) );
    }


    public function testCheckOptionForDefaultValue() : void {
        $cli = new MyTestInterpreter();
        $args = new Arguments( [] );
        $command = new MyTestCommand( $cli );
        $command->runOuter( $args );
        self::assertTrue( $command->checkOptionRelay( 'foo', 'foo_default' ) );
    }


    public function testCheckOptionForNotDefined() : void {
        $cli = new MyTestInterpreter();
        $command = new MyTestCommand( $cli );
        $this->expectException( LogicException::class );
        $command->checkOptionRelay( 'bar', 'baz' );
    }


    public function testCheckOptionForStringValue() : void {
        $cli = new MyTestInterpreter();
        $args = new Arguments( [ '--foo=bar' ] );
        $command = new MyTestCommand( $cli );
        $command->runOuter( $args );
        self::assertTrue( $command->checkOptionRelay( 'foo', 'bar' ) );
    }


    public function testCheckOptionForTooEarly() : void {
        $cli = new MyTestInterpreter();
        $command = new MyTestCommand( $cli );
        $this->expectException( LogicException::class );
        $command->checkOptionRelay( 'foo', 'wont_work' );
    }


    public function testCheckOptionForTrueValueOnBooleanOption() : void {
        $cli = new MyTestInterpreter();
        $args = new Arguments( [ '--bar' ] );
        $command = new MyTestCommand( $cli );
        $command->runOuter( $args );
        self::assertTrue( $command->checkOptionRelay( 'bar', true ) );
    }


    public function testCheckOptionForTrueValueOnStringOption() : void {
        $cli = new MyTestInterpreter();
        $args = new Arguments( [ '--foo' ] );
        $command = new MyTestCommand( $cli );
        $command->runOuter( $args );
        self::assertTrue( $command->checkOptionRelay( 'foo', 'foo_default' ) );
    }


    public function testMissingArgument() : void {
        $log = new BufferLogger();
        $cli = new MyTestInterpreter( i_log: $log );
        $command = new MyTestCommand( $cli, function ( Arguments $args ) {
            $args->shiftStringEx();
        } );
        $args = new Arguments( [] );
        $command->runOuter( $args );
        $le = $log->shiftLog();
        self::assertSame( 'Missing argument', $le->message );
    }


    public function testCheckOptionForUndefinedOption() : void {
        # Distinct from testCheckOptionForNotDefined: here we have already
        # handled options (so nrOptions is set) and ask about an option name
        # that was never declared.
        $cli = new MyTestInterpreter();
        $command = new MyTestCommand( $cli );
        $command->runOuter( new Arguments( [] ) );
        $this->expectException( LogicException::class );
        $command->checkOptionRelay( 'never_declared', 'value' );
    }


    public function testGetAliasesForNullConstant() : void {
        $cli = new MyTestInterpreter();
        $cmd = new class( $cli ) extends AbstractCommand {


            protected const array|string|null ALIASES = null;


            protected function run( Arguments $i_args ) : void {}


        };
        self::assertSame( [], $cmd->getAliases() );
    }


    public function testGetAliasesForStringConstant() : void {
        $cli = new MyTestInterpreter();
        $cmd = new class( $cli ) extends AbstractCommand {


            protected const array|string|null ALIASES = 'just-one';


            protected function run( Arguments $i_args ) : void {}


        };
        self::assertSame( [ 'just-one' ], $cmd->getAliases() );
    }


    public function testHandleExceptionPrintsUsageWhenHelpIsDefined() : void {
        $log = new BufferLogger();
        $cli = new MyTestInterpreter( i_log: $log );
        $cmd = new class( $cli ) extends Command {


            protected const string COMMAND = 'helpcmd';

            protected const string HELP    = 'A command with help text.';

            protected const string USAGE   = 'helpcmd <required>';


            protected function run( Arguments $args ) : void {
                $args->shiftStringEx();
            }


        };
        ob_start();
        $cmd->runOuter( new Arguments( [] ) );
        $st = OK::ob_get_clean();
        self::assertStringContainsString( 'Usage:', $st );
        self::assertStringContainsString( 'helpcmd <required>', $st );
    }


    public function testLogDeprecatedHelpersForwardToCli() : void {
        $log = new BufferLogger();
        $cli = new MyTestInterpreter( i_log: $log );
        $cmd = new class( $cli ) extends MyTestCommand {


            /** @suppress PhanDeprecatedFunction Verifying the deprecated helper still works. */
            public function logErrorRelay( string|Stringable $i_st ) : void {
                $this->logError( $i_st );
            }


            /** @suppress PhanDeprecatedFunction Verifying the deprecated helper still works. */
            public function logInfoRelay( string|Stringable $i_st ) : void {
                $this->logInfo( $i_st );
            }


            /** @suppress PhanDeprecatedFunction Verifying the deprecated helper still works. */
            public function logWarningRelay( string $i_st ) : void {
                $this->logWarning( $i_st );
            }


        };
        $cmd->logErrorRelay( 'err' );
        $cmd->logInfoRelay( 'info' );
        $cmd->logWarningRelay( 'warn' );
        self::assertCount( 3, $log );
        $le = $log->shiftLog();
        self::assertSame( LogLevel::ERROR, $le->level );
        self::assertSame( 'err', $le->message );
        $le = $log->shiftLog();
        self::assertSame( LogLevel::INFO, $le->level );
        self::assertSame( 'info', $le->message );
        $le = $log->shiftLog();
        self::assertSame( LogLevel::WARNING, $le->level );
        self::assertSame( 'warn', $le->message );
    }


    public function testLogForwardsThroughLoggerTrait() : void {
        # AbstractCommand::log is what the PSR-3 LoggerTrait helpers (info, warning, etc.)
        # ultimately call; verify it routes the entry to the interpreter's logger.
        $log = new BufferLogger();
        $cli = new MyTestInterpreter( i_log: $log );
        $cmd = new MyTestCommand( $cli );
        $cmd->info( 'hello via log()' );
        self::assertCount( 1, $log );
        $le = $log->shiftLog();
        self::assertSame( LogLevel::INFO, $le->level );
        self::assertSame( 'hello via log()', $le->message );
    }


    public function testRun() : void {
        $cli = new MyTestInterpreter();
        $command = new MyTestCommand( $cli );
        $args = new Arguments( [ 'foo', 'bar' ] );
        $command->runOuter( $args );
        self::assertSame( $args, $command->args );
    }


    public function testRunOuterRethrowsNonArgumentException() : void {
        $cli = new MyTestInterpreter();
        $cmd = new MyTestCommand( $cli, function ( Arguments $args ) : void {
            throw new RuntimeException( 'not an argument exception' );
        } );
        $this->expectException( RuntimeException::class );
        $cmd->runOuter( new Arguments( [] ) );
    }


}
