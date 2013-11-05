<?php
namespace WakePHP\Objects;
use PHPDaemon\Exceptions\UndefinedMethodCalled;
use PHPDaemon\Core\Daemon;
use PHPDaemon\Core\Debug;

/**
 * Class Generic
 * @package WakePHP\Objects
 */
abstract class Generic implements \ArrayAccess {
	use \PHPDaemon\Traits\StaticObjectWatchdog;
	public $orm;

	protected $update = [];

	protected $obj;

	protected $cond;

	protected $inited = false;

	public function __construct($cond, $objOrCb, $orm) {
		$this->orm = $orm;
		$this->cond = $cond;
		if (is_array($objOrCb) && !isset($objOrCb[0])) {
		}
		elseif (is_callable($objOrCb)) {
			$this->fetch($objOrCb);
		}
		elseif (is_array($objOrCb)) {
			$this->obj = $objOrCb;
			$this->inited = true;
			$this->init();
		}
	}

	public function getObject() {
		return $this->obj;
	}

	/**
	 *
	 */
	protected function init() {
	}

	public function getProperty($prop) {
		return $this->obj[$prop];
	}

	public function unsetProperty($prop) {
		unset($this->obj[$prop]);
	}

	public function setProperty($prop, $value) {
		$this->obj[$prop] = $value;
		if (!isset($this->update['$set'])) {
			$this->update['$set'] = [];
		}
		$this->update['$set'][$prop] = $value;
	}

	public function __get($prop) {
		return call_user_func([$this, 'get' . ucfirst($prop)]);
	}
	public function __isset($prop) {
		return call_user_func([$this, 'isset' . ucfirst($prop)]);
	}
	public function __set($prop, $value) {
		call_user_func([$this, 'set' . ucfirst($prop)], $value);
	}
	public function __unset($prop) {
		call_user_func([$this, 'unset' . ucfirst($prop)]);
	}

	/**
	 * @param string $method
	 * @param array $args
	 * @return null|mixed
	 */
	public function __call($method, $args) {
		if (strncmp($method, 'get', 3) === 0) {
			$name = lcfirst(substr($method, 3));
			return $this->getProperty($name);
		}
		if (strncmp($method, 'set', 3) === 0) {
			$name = lcfirst(substr($method, 3));
			$value = sizeof($args) ? $args[0] : null;
			$this->setProperty($name, $value);
			return;
		}
		if (strncmp($method, 'unset', 5) === 0) {
			$name = lcfirst(substr($method, 5));
			$this->unsetProperty($name);
			return;
		}
		throw new UndefinedMethodCalled('Call to undefined method ' . get_class($this) . '->' . $method);
	}

	public function fetch($cb) {
		$this->fetchObject(function($obj) use ($cb) {
			$this->obj = $obj;
			if (!$this->inited) {
				$this->inited = true;
				$this->init();
			}
			call_user_func($cb, $this);
		});
	}

	abstract protected function fetchObject($cb);

	public function save($cb) {
		$this->saveObject($cb);
	}

	abstract protected function saveObject($cb);


	/**
	 * Checks if property exists
	 * @param string Property name
	 * @return boolean Exists?
	 */

	public function offsetExists($prop) {
		return call_user_func([$this, 'get' . ucfirst($prop)]) !== null;
	}

	/**
	 * Get property by name
	 * @param string Property name
	 * @return mixed
	 */
	public function offsetGet($prop) {
		return call_user_func([$this, 'get' . ucfirst($prop)]);;
	}

	/**
	 * Set property
	 * @param string Property name
	 * @param mixed  Value
	 * @return void
	 */
	public function offsetSet($prop, $value) {
		call_user_func([$this, 'set' . ucfirst($prop)], $value);
	}

	/**
	 * Unset property
	 * @param string Property name
	 * @return void
	 */
	public function offsetUnset($prop) {
		call_user_func([$this, 'unset' . ucfirst($prop)]);
	}
}

