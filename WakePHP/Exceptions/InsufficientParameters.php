<?php
namespace WakePHP\Exceptions;
use PHPDaemon\Core\ComplexJob;
use PHPDaemon\Core\Daemon;
use PHPDaemon\Core\Debug;
use PHPDaemon\Core\ClassFinder;

/**
 * Class InsufficientParameters
 * @package WakePHP\Exceptions
 * @dynamic_fields
 */
class InsufficientParameters extends \Exception {
	protected $silent = false;
}
