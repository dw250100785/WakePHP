<?php
namespace WakePHP\ORM;
use WakePHP\Core\WakePHP;
use PHPDaemon\Exceptions\UndefinedMethodCalled;
use PHPDaemon\Core\Daemon;
use PHPDaemon\Core\Debug;
use PHPDaemon\Core\ClassFinder;
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

	protected $name;

	/**
	 * @param WakePHP $appInstance
	 */
	public function __construct($appInstance) {
		$this->appInstance = $appInstance;
		$this->init();
		$this->name = ClassFinder::getClassBasename($this);
	}

	public function getObject($type, $cond, $objOrCb = null) {
		$class = ClassFinder::find($type, $this->name, 'WakePHP\\Objects');
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
		if (strncmp($method, 'get', 3) === 0) {
			$type = substr($method, 3);
			$cond = sizeof($args) ? $args[0] : null;
			$objOrCb = sizeof($args) ? $args[1] : null;
			if ($obj = $this->getObject($type, $cond, $objOrCb)) {
				return $obj;
			}
		}
		elseif (strncmp($method, 'save', 4) === 0) {
			$type = substr($method, 4);
			if (!sizeof($args)) {
				return;
			}
			$obj = $args[0];
			$cb = isset($args[1]) ? $args[1] : null;
			$cond = isset($args[2]) ? $args[2] : null;
			if ($obj = $this->getObject($type, $cond, $obj)) {
				if ($cond === null) {
					$this->extractCondFrom($obj);
				}
				$obj->save($cb);
				return $obj;
			}
		}
		elseif (strncmp($method, 'count', 5) === 0) {
			$type = substr($method, 5);
			if (!sizeof($args)) {
				return;
			}
			$cb = $args[0];
			$cond = isset($args[1]) ? $args[1] : null;
			if ($obj = $this->getObject($type, $cond)) {
				$obj->count($cb);
				return $obj;
			}
		}
		elseif (strncmp($method, 'update', 6) === 0) {
			$type = substr($method, 6);
			if (!sizeof($args)) {
				return;
			}
			$cond = $args[0];
			$update = isset($args[1]) ? $args[1] : null;
			$cb = isset($args[2]) ? $args[2] : null;
			if ($obj = $this->getObject($type, $cond)) {
				$obj->attr($update);
				$obj->save($cb);
				return $obj;
			}
		}
		elseif ((strncmp($method, 'remove', 6) === 0) || (strncmp($method, 'delete', 6) === 0)) {
			$type = substr($method, 6);
			if (!sizeof($args)) {
				return;
			}
			$cond = $args[0];
			$cb = isset($args[1]) ? $args[1] : null;
			if ($obj = $this->getObject($type, $cond)) {
				$obj->remove($cb);
				return $obj;
			}
		}
		throw new UndefinedMethodCalled('Call to undefined method ' . get_class($this) . '->' . $method);
	}
}

