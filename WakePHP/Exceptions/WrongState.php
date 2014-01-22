<?php
namespace WakePHP\Exceptions;
use PHPDaemon\Core\ComplexJob;
use PHPDaemon\Core\Daemon;
use PHPDaemon\Core\Debug;
use PHPDaemon\Core\ClassFinder;

/**
 * Class WrongState
 * @package WakePHP\Exceptions
 * @dynamic_fields
 */
class WrongState extends \Exception {
	protected $silent = false;
}
