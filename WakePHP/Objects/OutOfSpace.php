<?php
namespace WakePHP\Exceptions;
use PHPDaemon\Core\ComplexJob;
use PHPDaemon\Core\Daemon;
use PHPDaemon\Core\Debug;
use WakePHP\Core\Request;

/**
 * Class OutOfSpace
 * @package WakePHP\Actions
 * @dynamic_fields
 */
class OutOfSpace extends Generic {
	public $silent = true;
}
