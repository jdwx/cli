<?php


declare( strict_types = 1 );


namespace JDWX\CLI;


/**
 * @deprecated Use JDWX\App\StderrLogger directly.
 * @suppress PhanDeprecatedClass
 * @noinspection PhpDeprecationInspection
 *
 * This is only here for backwards compatibility; the StderrLogger class
 * moved to the JDWX\App package.
 *
 * Will be removed for 1.1.0.
 */
class StderrLogger extends \JDWX\App\StderrLogger {
}
