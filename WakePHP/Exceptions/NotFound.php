<?php
namespace WakePHP\Exceptions;
use PHPDaemon\Core\ComplexJob;
use PHPDaemon\Core\Daemon;
use PHPDaemon\Core\Debug;
use PHPDaemon\Core\ClassFinder;

/**
 * Class NotFound
 * @package WakePHP\Exceptions
 * @dynamic_fields
 */
class NotFound extends Generic {
	protected $silent = true;
}
