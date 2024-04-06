# jdwx/cli

PHP framework for developing extensible PHP CLIs.

This provides a strong foundation for developing PHP command-line interfaces (CLIs).
It is designed to be easy to use and easy to extend and to provide features that can
be tedious to implement from scratch.

It provides:
* Command line editing through readline.
* Command parser with support for single and double-quoted strings, escape sequences, and backquoted 
  command substitution.
* Variable substitution in command strings. (Supports both ${var} and $var syntax.)
* Command autocompletion and context-aware help.
* A handful of common built-in commands (e.g., echo and expr).
* Command line history view, search, and re-run.
* \# comments.

## Installation

You can require it directly with Composer:

```bash
composer require jdwx/cli
```

Or download the source from GitHub: https://github.com/jdwx/args.git

## Requirements

This library requires PHP 8.2 or later with the readline extension. 
It might work with earlier versions of PHP 8, but it has not been tested with them.

## Usage 

Using this framework involves two steps:

1) Creating commands that extend the AbstractCommand class and implement a run() method.
   These classes have constant properties that define the command name, help text, usage
   examples, aliases and (where applicable) predefined options. See the builtin commands
   for details.

2) Subclass the Interpreter class and add your commands in the constructor with the 
   addCommandClass() method. Instantiate your interpreter subclass and call the 
   run() method to start the interpreter. The php_sh.php file in the bin directory
   provides a simple example of how to do this.

Arguments are passed to the command using the [Arguments](https://github.com/jdwx/args) 
class. For complex applications, you may want to subclass Arguments to provide additional
methods to handle types relevant to your application. If you do, it is generally helpful
to create an abstract subclass of AbstractCommand that defines a signature for the run()
method using your custom Arguments subclass. E.g.:

```php
abstract class MyCommand extends AbstractCommand {

    abstract public function run( MyArguments $args );
    
}
```

Then use that as the parent class of your custom commands.

The Command class is provided for use when the generic Arguments implementation is
sufficient.

## Stability

This framework is considered stable and is used in production code. However, it has
been refactored and improved for general use, and those features may not be as 
well-tested.

## History

This framework been in production for many years. It was refactored out of a larger
codebase and released as an open-source standalone module in 2024.

There is a method called addCommand() that is present for historical reasons. It is
used extensively in legacy code and is retained for compatibility. However, it 
absolutely **must not** be used in new code. Using this results in 10,000-line 
classes. We look forward to removing it someday. When that day arrives, no advance
warning will be given.
