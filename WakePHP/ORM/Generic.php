<?php
namespace WakePHP\ORM;
use WakePHP\Core\WakePHP;

/**
 * Class ORM
 * @package WakePHP\Core
 */
abstract class Generic {
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

	public function getObject($type, $cond, $objOrCb = null) {
		$class = '\\WakePHP\\Objects\\' . $type;
		if (!class_exists($class)) {
			Daemon::log(get_class($this) . ': undefined class: ' . $class);
			return false;
		}
		return new $class($cond, $objOrCb, $this);
	}

	public function getDummy($init) {
		return $this->getObject('Dummy', $init);
	}

	/**
	 * @param string $method
	 * @param array $args
	 * @return null|mixed
	 */
	public function __call($method, $args) {
		if (substr($method, -3) === 'get') {
			$type = substr($method, 3);
			$cond = sizeof($args) ? $args[0] : null;
			$objOrCb = sizeof($args) ? $args[1] : null;
			if ($obj = $this->getObject($type, $cond, $objOrCb)) {
				return $obj;
			}
		}
		throw new UndefinedMethodCalled('Call to undefined method ' . get_class($this) . '->' . $method);
	}
}

