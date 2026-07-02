<?php


declare( strict_types = 1 );


namespace JDWX\CLI\Tests;


use InvalidArgumentException;
use JDWX\Args\Arguments;
use JDWX\Args\Exceptions\BadArgumentException;
use JDWX\Args\Exceptions\ExtraArgumentsException;
use JDWX\CLI\AbstractCommand;
use JDWX\CLI\BaseInterpreter;
use JDWX\Log\BufferLogger;
use JDWX\Strict\OK;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use Psr\Log\LogLevel;
use RuntimeException;


require_once __DIR__ . '/MyTestInterpreter.php';
require_once __DIR__ . '/TestCommands/AbstractFixtureCommand.php';
require_once __DIR__ . '/TestCommands/NotACommandFixture.php';
require_once __DIR__ . '/TestCommands/ValidFixtureCommand.php';


#[CoversClass( BaseInterpreter::class )]
final class BaseInterpreterTest extends TestCase {


    public function testAddCommandDirectoryForMissingDirectory() : void {
        $cli = new MyTestInterpreter();
        $this->expectException( RuntimeException::class );
        $cli->addCommandDirectoryRelay( 'JDWX\\CLI\\Tests\\NoSuchNamespace', '/does/not/exist' );
    }


    public function testAddCommandDirectoryRegistersValidAndSkipsOthers() : void {
        $cli = new MyTestInterpreter();
        # The TestCommands fixture directory contains an abstract subclass, a
        # class that does not extend AbstractCommand, and a valid command.
        # The first two should be skipped silently; the third should be
        # registered and reachable via the interpreter.
        $cli->addCommandDirectoryRelay( 'JDWX\\CLI\\Tests\\TestCommands', __DIR__ . '/TestCommands' );

        ob_start();
        $cli->showHelp();
        $stHelp = OK::ob_get_clean();
        self::assertStringNotContainsString( 'AbstractFixture', $stHelp );
        self::assertStringNotContainsString( 'NotACommand', $stHelp );
        self::assertStringContainsString( 'fixturecmd', $stHelp );
        self::assertStringContainsString( 'A fixture command for tests.', $stHelp );

        $cli->readLines = [ 'fixturecmd' ];
        ob_start();
        $cli->run();
        $stRun = OK::ob_get_clean();
        self::assertStringContainsString( 'fixture-output', $stRun );
    }


    public function testAddCommandForDuplicate() : void {
        $cli = new MyTestInterpreter();
        $cli->addCommandRelay( 'example', 'commandExample' );
        $this->expectException( InvalidArgumentException::class );
        $cli->addCommandRelay( 'example', 'commandExample2' );
    }


    public function testAddCommandForMissingCommand() : void {
        $cli = new class() extends MyTestInterpreter {


            /** @noinspection PhpUnused */
            public function commandExample() : void {
                echo 'Not used.\n';
            }


        };
        $this->expectException( InvalidArgumentException::class );
        $cli->addCommandRelay( BaseInterpreter::DEFAULT_COMMAND, 'commandExample' );
    }


    public function testAddCommandForUsageNotBeginningWithCommand() : void {
        # renameCommand() depends on every usage string beginning with its
        # command name, so addCommand() must reject usage that doesn't.
        $cli = new MyTestInterpreter();
        $this->expectException( InvalidArgumentException::class );
        $cli->addCommandRelay( 'example', 'commandExample', 'Help text.', 'unrelated <usage>' );
    }


    public function testAddCommandObjectForMissingCommand() : void {

        $cli = new MyTestInterpreter();
        $cmd = new class( $cli ) extends AbstractCommand {


            protected function run( Arguments $i_args ) : void {}


        };

        $this->expectException( InvalidArgumentException::class );
        $cli->addCommandObjectRelay( $cmd );
    }


    public function testGetHistoryAfterCommandsRun() : void {
        $cli = new MyTestInterpreter();
        self::assertSame( [], $cli->getHistory() );
        $cli->readLines = [ 'echo first', 'echo second' ];
        ob_start();
        $cli->run();
        OK::ob_get_clean();
        self::assertSame( [ 'echo first', 'echo second' ], $cli->getHistory() );
    }


    public function testHandleArgumentExceptionForBadArgument() : void {
        $log = new BufferLogger();
        $cli = new MyTestInterpreter( i_log: $log );
        self::assertTrue(
            $cli->handleArgumentException( new BadArgumentException( 'naughty', 'bad value' ) )
        );
        $le = $log->shiftLog();
        self::assertSame( LogLevel::ERROR, $le->level );
        self::assertSame( 'bad value', $le->message );
        self::assertSame( 'naughty', $le->context[ 'value' ] );
    }


    public function testHandleArgumentExceptionForExtraArguments() : void {
        $log = new BufferLogger();
        $cli = new MyTestInterpreter( i_log: $log );
        self::assertTrue(
            $cli->handleArgumentException( new ExtraArgumentsException( [ 'x', 'y' ] ) )
        );
        $le = $log->shiftLog();
        self::assertSame( LogLevel::ERROR, $le->level );
        self::assertSame( [ 'x', 'y' ], $le->context[ 'extra' ] );
    }


    public function testHandleArgumentExceptionForNonArgumentException() : void {
        $cli = new MyTestInterpreter();
        self::assertFalse( $cli->handleArgumentException( new RuntimeException( 'nope' ) ) );
    }


    public function testHandleCommandForBangHistoryMatch() : void {
        $cli = new MyTestInterpreter();
        $cli->readLines = [ 'echo first', '!echo' ];
        ob_start();
        $cli->run();
        $st = OK::ob_get_clean();
        # The history-recall should have re-run the prior 'echo first' line.
        self::assertSame( 2, substr_count( $st, 'first' ) );
    }


    public function testHandleCommandForBangHistoryNoMatch() : void {
        $log = new BufferLogger();
        $cli = new MyTestInterpreter( i_log: $log );
        $cli->readLines = [ '!nothingmatchesthis' ];
        $cli->run();
        $found = false;
        $count = count( $log );
        for ( $ii = 0 ; $ii < $count ; $ii++ ) {
            $le = $log->shiftLog();
            if ( str_contains( $le->message, 'No match in history' ) ) {
                $found = true;
            }
        }
        self::assertTrue( $found );
    }


    public function testHandleCommandForCommentLine() : void {
        $cli = new MyTestInterpreter();
        $cli->readLines = [ '# whole-line comment' ];
        ob_start();
        $cli->run();
        $st = OK::ob_get_clean();
        self::assertSame( '', trim( $st ) );
    }


    public function testHandleCommandForHelpQuestion() : void {
        $cli = new MyTestInterpreter();
        $cli->readLines = [ 'echo ?' ];
        ob_start();
        $cli->run();
        $st = OK::ob_get_clean();
        self::assertStringContainsString( 'Echo', $st );
    }


    public function testHandleCommandForParseError() : void {
        $log = new BufferLogger();
        $cli = new MyTestInterpreter( i_log: $log );
        $cli->readLines = [ 'echo "unterminated' ];
        $cli->run();
        $found = false;
        $count = count( $log );
        for ( $ii = 0 ; $ii < $count ; $ii++ ) {
            $le = $log->shiftLog();
            var_dump( $le );
            if ( str_contains( $le->message, 'Unmatched' ) ) {
                $found = true;
            }
        }
        self::assertTrue( $found );
    }


    public function testHandleCommandForUnknownCommand() : void {
        $log = new BufferLogger();
        $cli = new MyTestInterpreter( i_log: $log );
        $cli->readLines = [ 'zzznotacommand' ];
        $cli->run();
        $found = false;
        $count = count( $log );
        for ( $ii = 0 ; $ii < $count ; $ii++ ) {
            $le = $log->shiftLog();
            if ( str_contains( $le->message, 'Unknown command' ) ) {
                $found = true;
            }
        }
        self::assertTrue( $found );
    }


    public function testHandleInputForEmptyLine() : void {
        # An empty input line should be skipped without halting the main loop.
        $cli = new MyTestInterpreter();
        $cli->readLines = [ '', 'echo hello' ];
        ob_start();
        $cli->run();
        $st = OK::ob_get_clean();
        self::assertStringContainsString( 'hello', $st );
    }


    public function testReadlineCompletionForDeduplicatedFirstWord() : void {
        # When several commands share their first word but differ in later words,
        # readlineCompletion should collapse them down to the shared first word.
        $cli = new MyTestInterpreter();
        $cli->lineBuffer = 'his';
        $cli->end = strlen( $cli->lineBuffer );
        $r = $cli->readlineCompletion( 'unused', 0 );
        self::assertSame( [ 'history' ], $r );
    }


    public function testReadlineCompletionForExactMatch() : void {
        # An exact, single-match completion should also display the command's
        # help text as a side effect.
        $cli = new MyTestInterpreter();
        $cli->lineBuffer = 'echo';
        $cli->end = strlen( $cli->lineBuffer );
        ob_start();
        $r = $cli->readlineCompletion( 'unused', 0 );
        $st = OK::ob_get_clean();
        self::assertSame( [ 'echo' ], $r );
        self::assertStringContainsString( 'Echo', $st );
    }


    public function testRenameCommandForAbstractCommand() : void {
        # The renamed command should dispatch under its new name.
        $cli = new MyTestInterpreter();
        $cli->renameCommandRelay( 'set', 'setvar' );
        $cli->readLines = [ 'setvar x 5' ];
        ob_start();
        $cli->run();
        OK::ob_get_clean();
        self::assertSame( '5', $cli->getVariable( 'x' ) );
    }


    public function testRenameCommandForAbstractCommandHelp() : void {
        # The help entry should be findable under the new name and the old
        # usage string should be gone.
        $cli = new MyTestInterpreter();
        $cli->renameCommandRelay( 'set', 'setvar' );

        ob_start();
        $cli->showHelp( [ 'setvar' ] );
        $st = OK::ob_get_clean();
        self::assertStringContainsString( 'setvar <variable> <value...>', $st );
        self::assertStringContainsString( 'Set a variable.', $st );

        ob_start();
        $cli->showHelp();
        $st = OK::ob_get_clean();
        self::assertStringNotContainsString( 'set <variable>', $st );
    }


    public function testRenameCommandForAbstractCommandUsage() : void {
        # The usage shown when the command's arguments are wrong should use
        # the new name.
        $log = new BufferLogger();
        $cli = new MyTestInterpreter( i_log: $log );
        $cli->renameCommandRelay( 'set', 'setvar' );
        $cli->readLines = [ 'setvar' ];
        ob_start();
        $cli->run();
        $st = OK::ob_get_clean();
        self::assertStringContainsString( 'Usage: setvar <variable> <value...>', $st );
    }


    public function testRenameCommandForAlias() : void {
        # Renaming an alias must not affect the primary name or the name the
        # shared command object reports.
        $log = new BufferLogger();
        $cli = new MyTestInterpreter( i_log: $log );
        $cli->renameCommandRelay( 'quit', 'leave' );

        $cli->readLines = [ 'quit', 'leave', 'echo hello' ];
        ob_start();
        $cli->run();
        $st = OK::ob_get_clean();
        # The old alias is unknown, the new one exits before the echo runs.
        self::assertStringNotContainsString( 'hello', $st );
        $found = false;
        $count = count( $log );
        for ( $ii = 0 ; $ii < $count ; $ii++ ) {
            $le = $log->shiftLog();
            if ( str_contains( $le->message, 'Unknown command: quit' ) ) {
                $found = true;
            }
        }
        self::assertTrue( $found );

        # The alias's help entry follows the rename; the primary's does not.
        ob_start();
        $cli->showHelp( [ 'leave' ] );
        $st = OK::ob_get_clean();
        self::assertStringContainsString( 'Exit the program.', $st );
        ob_start();
        $cli->showHelp( [ 'exit' ] );
        $st = OK::ob_get_clean();
        self::assertStringContainsString( 'Exit the program.', $st );
    }


    public function testRenameCommandForExistingTarget() : void {
        $cli = new MyTestInterpreter();
        $cli->addCommandRelay( 'aaa', 'commandAaa' );
        $cli->addCommandRelay( 'bbb', 'commandBbb' );
        $this->expectException( InvalidArgumentException::class );
        $cli->renameCommandRelay( 'aaa', 'bbb' );
    }


    public function testRenameCommandForMissingCommand() : void {
        $cli = new MyTestInterpreter();
        $this->expectException( InvalidArgumentException::class );
        $cli->renameCommandRelay( 'zzznotacommand', 'whatever' );
    }


    public function testRenameCommandForSubcommandCollision() : void {
        # If any name in the renamed family collides, nothing should be
        # renamed, even names earlier in the list that were collision-free.
        $cli = new class extends MyTestInterpreter {


            public function __construct() {
                parent::__construct();
                $this->addCommandRelay( 'fam', 'commandFam' );
                $this->addCommandRelay( 'fam sub', 'commandFamSub' );
                $this->addCommandRelay( 'group sub', 'commandGroupSub' );
            }


            /** @noinspection PhpUnused */
            public function commandFam( Arguments $args ) : void {
                echo "fam-ran\n";
            }


            /** @noinspection PhpUnused */
            public function commandFamSub( Arguments $args ) : void {
                echo "fam-sub-ran\n";
            }


            /** @noinspection PhpUnused */
            public function commandGroupSub( Arguments $args ) : void {
                echo "group-sub-ran\n";
            }


        };
        try {
            $cli->renameCommandRelay( 'fam', 'group' );
            self::fail( 'Expected InvalidArgumentException.' );
        } catch ( InvalidArgumentException $ex ) {
            self::assertStringContainsString( 'group sub', $ex->getMessage() );
        }
        $cli->readLines = [ 'fam', 'fam sub' ];
        ob_start();
        $cli->run();
        $st = OK::ob_get_clean();
        self::assertStringContainsString( 'fam-ran', $st );
        self::assertStringContainsString( 'fam-sub-ran', $st );
    }


    public function testRenameCommandForSubcommands() : void {
        $log = new BufferLogger();
        $cli = new class( '> ', null, $log ) extends MyTestInterpreter {


            public function __construct( string           $i_stPrompt = '> ', array|Arguments|null $i_argv = null,
                                         ?LoggerInterface $i_log = null ) {
                parent::__construct( $i_stPrompt, $i_argv, $i_log );
                $this->addCommandRelay( 'fam', 'commandFam' );
                $this->addCommandRelay( 'fam sub', 'commandFamSub' );
            }


            /** @noinspection PhpUnused */
            public function commandFam( Arguments $args ) : void {
                echo "fam-ran\n";
            }


            /** @noinspection PhpUnused */
            public function commandFamSub( Arguments $args ) : void {
                echo "fam-sub-ran\n";
            }


        };
        $cli->renameCommandRelay( 'fam', 'group' );
        $cli->readLines = [ 'group', 'group sub', 'fam' ];
        ob_start();
        $cli->run();
        $st = OK::ob_get_clean();
        self::assertStringContainsString( 'fam-ran', $st );
        self::assertStringContainsString( 'fam-sub-ran', $st );
        # The old name should no longer be recognized.
        $found = false;
        $count = count( $log );
        for ( $ii = 0 ; $ii < $count ; $ii++ ) {
            $le = $log->shiftLog();
            if ( str_contains( $le->message, 'Unknown command: fam' ) ) {
                $found = true;
            }
        }
        self::assertTrue( $found );
    }


    public function testRunCommandForMissingMethod() : void {
        $log = new BufferLogger();
        $cli = new class( '> ', null, $log ) extends MyTestInterpreter {


            public function __construct( string           $i_stPrompt = '> ', array|Arguments|null $i_argv = null,
                                         ?LoggerInterface $i_log = null ) {
                parent::__construct( $i_stPrompt, $i_argv, $i_log );
                $this->addCommandRelay( 'phantom', 'commandPhantomThatDoesNotExist' );
            }


        };
        $cli->readLines = [ 'phantom' ];
        $cli->run();
        $found = false;
        $count = count( $log );
        for ( $ii = 0 ; $ii < $count ; $ii++ ) {
            $le = $log->shiftLog();
            if ( str_contains( $le->message, 'No implementation' ) ) {
                $found = true;
            }
        }
        self::assertTrue( $found );
    }


    public function testRunCommandForStringMethod() : void {
        $cli = new class extends MyTestInterpreter {


            public ?Arguments $captured = null;


            public function __construct() {
                parent::__construct();
                $this->addCommandRelay( 'mycmd', 'commandMyCmd' );
            }


            /** @noinspection PhpUnused */
            public function commandMyCmd( Arguments $args ) : void {
                $this->captured = $args;
            }


        };
        $cli->readLines = [ 'mycmd foo bar' ];
        $cli->run();
        self::assertNotNull( $cli->captured );
        self::assertSame( [ 'mycmd foo bar' ], $cli->getHistory() );
    }


    public function testRunCommandForStringMethodThrowingArgumentException() : void {
        $log = new BufferLogger();
        $cli = new class( '> ', null, $log ) extends MyTestInterpreter {


            public function __construct( string           $i_stPrompt = '> ', array|Arguments|null $i_argv = null,
                                         ?LoggerInterface $i_log = null ) {
                parent::__construct( $i_stPrompt, $i_argv, $i_log );
                $this->addCommandRelay( 'argbomb', 'commandArgBomb' );
            }


            /** @noinspection PhpUnused */
            public function commandArgBomb( Arguments $args ) : void {
                $args->shiftStringEx();
            }


        };
        $cli->readLines = [ 'argbomb' ];
        $cli->run();
        $found = false;
        $count = count( $log );
        for ( $ii = 0 ; $ii < $count ; $ii++ ) {
            $le = $log->shiftLog();
            if ( str_contains( $le->message, 'Missing argument' ) ) {
                $found = true;
            }
        }
        self::assertTrue( $found );
        # Commands that fail with an argument exception are not added to history.
        self::assertSame( [], $cli->getHistory() );
    }


    public function testRunCommandForStringMethodThrowingNonArgumentException() : void {
        $cli = new class extends MyTestInterpreter {


            public function __construct() {
                parent::__construct();
                $this->addCommandRelay( 'rtbomb', 'commandRtBomb' );
            }


            /** @noinspection PhpUnused */
            public function commandRtBomb( Arguments $args ) : void {
                throw new RuntimeException( 'boom' );
            }


        };
        $cli->readLines = [ 'rtbomb' ];
        $cli->run();
        self::assertInstanceOf( RuntimeException::class, $cli->ex );
        self::assertSame( 'boom', $cli->ex->getMessage() );
    }


    public function testShowHelpForAlias() : void {
        # An alias gets its own help entry keyed by the alias name, so help
        # lookup works for the alias, not just the primary command name.
        $cli = new MyTestInterpreter();
        ob_start();
        $cli->showHelp( [ 'quit' ] );
        $st = OK::ob_get_clean();
        self::assertStringContainsString( 'quit', $st );
        self::assertStringContainsString( 'Exit the program.', $st );
    }


    public function testShowHelpWithoutCommandFilterListsAllCommands() : void {
        $cli = new MyTestInterpreter();
        ob_start();
        $cli->showHelp();
        $st = OK::ob_get_clean();
        self::assertStringContainsString( 'echo', $st );
        self::assertStringContainsString( 'exit', $st );
        self::assertStringContainsString( 'expr', $st );
    }


}
