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

	protected $new = false;

	protected $inited = false;

	protected $appInstance;

	public function __construct($cond, $objOrCb, $orm) {
		$this->orm = $orm;
		$this->appInstance = $orm->appInstance;
		$this->cond = $cond instanceof \MongoId ? ['_id' => $cond] : $cond;
		if (is_array($objOrCb) && !isset($objOrCb[0])) {
		}
		elseif (is_callable($objOrCb)) {
			$this->fetch($objOrCb);
		}
		elseif (is_array($objOrCb)) {
			$this->new = true;
			$this->obj = $objOrCb;
			if (!isset($this->obj['_id'])) {
				$this->obj['_id'] = new MongoId;
			}
			$this->inited = true;
			$this->init();
		}
	}

	public function attr($m, $n) {
		$c = func_num_args();
		if ($c === 1) {
			if (is_array($m)) {
				foreach ($m as $k => $v) {
					$this[$k] = $v;
				}
				return;
			}
			return $this[$m];
		} elseif ($c === 2) {
			$this[$m] = $n;
		} else {
			return $this->obj;
		}
	}

	public function extractCondFrom($obj) {
		if (isset($obj['_id'])) {
			$this->cond = ['_id' => $obj['_id']];
			if (is_string($this->cond['_id'])) {
				$this->cond['_id'] = new \MongoId($this->cond['_id']);
			}
		}
	}

	public function getObject() {
		return $this->obj;
	}

	public function getId() {
		return $this->obj['_id'];
	}

	/**
	 *
	 */
	protected function init() {
	}

	public function getProperty($prop) {
		return isset($this->obj[$prop]) ? $this->obj[$prop] : null;
	}

	public function unsetProperty($prop) {
		unset($this->obj[$prop]);
	}

	public function setProperty($prop, $value) {
		$this->obj[$prop] = $value;
		if ($this->new) {
			return;
		}
		if (!isset($this->update['$set'])) {
			$this->update['$set'] = [];
		}
		$this->update['$set'][$prop] = $value;
		return $this;
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
			if ($obj === false) {
				call_user_func($cb, false);
				return;
			}
			$this->new = false;
			if (!$this->inited) {
				$this->inited = true;
				$this->init();
			}
			call_user_func($cb, $this);
		});
	}

	public function exists() {
		return is_array($this->obj) ? true : ($this->obj === null ? null : false);
	}

	abstract protected function fetchObject($cb);

	public function count($cb) {
		$this->countObject($cb);
	}

	abstract protected function countObject($cb);

	public function remove($cb) {
		$this->new = true;
		$this->removeObject($cb);
	}

	abstract protected function removeObject($cb);

	public function save($cb) {
		$this->new = false;
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

