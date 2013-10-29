<?php
namespace WakePHP\Objects;

/**
 * Class Generic
 * @package WakePHP\Objects
 */
abstract class Generic {
	use \PHPDaemon\Traits\ClassWatchdog;
	use \PHPDaemon\Traits\StaticObjectWatchdog;

	public $orm;

	protected $_id;

	protected $update = [];

	protected $obj;

	public function __construct($init, $orm) {
		$this->orm = $orm;
		if (is_array($init)) {
			$this->obj = $init;
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
	public function init($init) {
		if (is_array($init)) {
			$this->obj = $init;
			if (isset($init['_id'])) {
				$this->_id = $init['_id'];
			}
		}
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

}

