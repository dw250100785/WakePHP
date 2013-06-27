<?php
namespace WakePHP\Core;

	/**
	 * ORM class.
	 */
/**
 * Class ORM
 * @package WakePHP\Core
 */
class ORM {
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

