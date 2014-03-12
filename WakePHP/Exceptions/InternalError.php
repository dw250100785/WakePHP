<?php
namespace WakePHP\Exceptions;
use PHPDaemon\Core\ComplexJob;
use PHPDaemon\Core\Daemon;
use PHPDaemon\Core\Debug;
use PHPDaemon\Core\ClassFinder;

/**
 * Class InternalError
 * @package WakePHP\Exceptions
 * @dynamic_fields
 */
class InternalError extends Generic {
	protected $silent = false;
}
