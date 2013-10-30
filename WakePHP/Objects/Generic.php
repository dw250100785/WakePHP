<?php
namespace WakePHP\Objects;

/**
 * Class Generic
 * @package WakePHP\Objects
 */
abstract class Generic implements \ArrayAccess {
	use \PHPDaemon\Traits\ClassWatchdog;
	use \PHPDaemon\Traits\StaticObjectWatchdog;

	public $orm;

	protected $_id;

	protected $update = [];

	protected $obj;

	protected $cond;

	public function __construct($cond, $objOrCb, $orm) {
		$this->orm = $orm;
		$this->cond = $cond;
		if (is_array($init) && !isset($init[0])) {
		}
		elseif (is_callable($objOrCb)) {
			$this->fetch($objOrCb);
		}
		if (is_array($objOrCb)) {
			$this->obj = $objOrCb;
			if (isset($init['_id'])) {
				$this->_id = $init['_id'];
			}
		} else {
			$this->_id = $init;
		}
		$this->init();
	}

	/**
	 *
	 */
	public function init() {
	}

	/**
	 * @param string $method
	 * @param array $args
	 * @return null|mixed
	 */
	public function __call($method, $args) {
		if (substr($method, -3) === 'get') {
			$name = substr($method, 3);
			if ($obj = $this->getProperty($name)) {
				return $obj;
			}
		}
		if (substr($method, -3) === 'set') {
			$name = substr($method, 3);
			$value = sizeof($args) ? $args[0] : null;
			if ($obj = $this->setProperty($name, $value)) {
				return $obj;
			}
		}
		throw new UndefinedMethodCalled('Call to undefined method ' . get_class($this) . '->' . $method);
	}

	public function fetch($cb) {

	}

	public function save($cb) {

	}


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

