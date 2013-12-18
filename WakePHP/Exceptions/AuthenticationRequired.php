<?php
namespace WakePHP\Exceptions;
use PHPDaemon\Core\ComplexJob;
use PHPDaemon\Core\Daemon;
use PHPDaemon\Core\Debug;
use WakePHP\Core\Request;

/**
 * Class AuthenticationRequired
 * @package WakePHP\Actions
 * @dynamic_fields
 */
class AuthenticationRequired extends Generic {
	public $silent = true;
}
