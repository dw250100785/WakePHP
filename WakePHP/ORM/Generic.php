<?php
namespace WakePHP\ORM;

	/**
	 * ORM class.
	 */
use WakePHP\Core\WakePHP;

/**
 * Class ORM
 * @package WakePHP\Core
 */
class Generic {
	use \PHPDaemon\Traits\ClassWatchdog;
	use \PHPDaemon\Traits\StaticObjectWatchdog;

	/**
	 * @var WakePHP
	 */
	public $appInstance;

	/**
	 * @param WakePHP $appInstance
	 */
	public function __construct($appInstance) {
		$this->appInstance = $appInstance;
		$this->init();
	}

	/**
	 *
	 */
	public function init() {
	}
}

