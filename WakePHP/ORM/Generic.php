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

	protected $ns = 'WakePHP\\Objects';

	/**
	 * @param WakePHP $appInstance
	 */
	public function __construct($appInstance) {
		$this->appInstance = $appInstance;
		$this->name = ClassFinder::getClassBasename($this);
		$this->init();
	}

	public function getObject($type, $cond = null, $objOrCb = null) {
		$class = ClassFinder::find($type, $this->name, $this->ns);
		if (!class_exists($class)) {
			Daemon::log(get_class($this) . ': undefined class: ' . $class);
			return false;
		}
		return new $class($cond, $objOrCb, $this);
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
			$objOrCb = sizeof($args) > 1 ? $args[1] : null;
			if (substr($type, -4) === 'ById') {
				$type = substr($type, 0, -4);
				if ($obj = $this->getObject($type)) {
					return $obj->condSetId($cond)->fetch($objOrCb);
				}
			}
			if ($obj = $this->getObject($type, $cond, $objOrCb)) {
				return $obj;
			}
		}
		elseif (strncmp($method, 'new', 3) === 0) {
			$type = substr($method, 3);
			$attrs = sizeof($args) ? $args[0] : null;
			$cb = sizeof($args) > 1 ? $args[1] : null;
			if ($obj = $this->getObject($type)) {
				$obj->create($attrs);
				if ($cb !== null) {
					$obj->save($cb);
				}
				return $obj;
			}
		}
		elseif (strncmp($method, 'wrap', 4) === 0) {
			$type = substr($method, 4);
			$attrs = sizeof($args) ? $args[0] : null;
			if ($obj = $this->getObject($type)) {
				$obj->wrap($attrs);
				return $obj;
			}
		}
		elseif (strncmp($method, 'save', 4) === 0) {
			$type = substr($method, 4);
			if (!sizeof($args)) {
				return;
			}
			$data = $args[0];
			$cb = isset($args[1]) ? $args[1] : null;
			$cond = isset($args[2]) ? $args[2] : null;
			if ($obj = $this->getObject($type, $cond, $data)) {
				if ($cond === null) {
					$obj->extractCondFrom($data);
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
			$cond = isset($args[1]) ? $args[1] : [];
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
				$obj->update($cb);
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

