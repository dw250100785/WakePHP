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
	
	protected $orm;

	protected $update = [];

	protected $obj;

	protected $cond;

	protected $new = false;

	protected $inited = false;

	protected $appInstance;

	protected $lastError;

	public function __construct($cond, $objOrCb, $orm) {
		$this->orm = $orm;
		$this->appInstance = $orm->appInstance;
		$this->cond = $cond instanceof \MongoId ? ['_id' => $cond] : $cond;
		if (is_array($objOrCb) && !isset($objOrCb[0])) {
			$this->create($objOrCb);
		}
		elseif (is_callable($objOrCb)) {
			$this->fetch($objOrCb);
		}
		elseif (is_array($objOrCb)) {
			$this->create($objOrCb);
		}
	}
	
	public function getLastError($bool = false) {
		if ($bool) {
			if (isset($this->lastError['updatedExisting'])) {
				return $this->lastError['updatedExisting'];
			}
			if (isset($this->lastError['ok'])) {
				return $this->lastError['ok'];
			}
			if (isset($this->lastError['$ok'])) {
				return $this->lastError['$ok'];
			}
			return false;
		}
		return $this->lastError;
	}

	protected function set($k, $v) {
		$this->obj[$k] = $v;
		if ($this->new) {
			return;
		}
		if (!isset($this->update['$set'])) {
			$this->update['$set'] = [];
		}
		$this->update['$set'][$k] = $v;
		return $this;
	}

	protected function inc($k, $v = 1) {
		if (!isset($this->obj[$k])) {
			$this->obj[$k] = $v;
		} else {
			$this->obj[$k] += $v;
		}
		if ($this->new) {
			return;
		}
		if (!isset($this->update['$inc'])) {
			$this->update['$inc'] = [$k => $v];
		} else {
			if (!isset($this->update['$inc'][$k])) {
				$this->update['$inc'][$k] = $v;
			} else {
				$this->update['$inc'][$k] += $v;
			}
		}
	}

	protected function push($k, $v) {
		if (isset($v['$each'])) {
			if (!isset($this->obj[$k])) {
				$this->obj[$k] = [];
			}
			foreach ($v['$each'] as $e) {
				$this->obj[$k][] = $e;
			}
		} else {
			if (!isset($this->obj[$k])) {
				$this->obj[$k] = [$v];
			} else {
				$this->obj[$k][] = $v;
			}
		}
		if ($this->new) {
			return;
		}
		if (!isset($this->update['$push'])) {
			$this->update['$push'] = [$k => $v];
		} else {
			$this->update['$push'][$k] = $v;
		}
	}

	public function create($obj = []) {
		$this->new = true;
		$this->obj = [];
		if ($obj !== null) {
			$this->attr($obj);
		}
		if (!isset($this->obj['_id'])) {
			$this->obj['_id'] = new \MongoId;
		}
		$this->inited = true;
		$this->init();
	}

	protected function cond() {
		if (!func_num_args()) {
			return $this->cond;
		}
		return $this->cond = func_get_arg(0);
	}

	public function attr($m, $n = null) {
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
		}
		return $this;
	}

	public function extractCondFrom($obj) {
		if (isset($obj['_id'])) {
			$this->cond = ['_id' => $obj['_id']];
			if (is_string($this->cond['_id'])) {
				$this->cond['_id'] = new \MongoId($this->cond['_id']);
			}
		}
		return $this;
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

	protected function getProperty($k) {
		return isset($this->obj[$k]) ? $this->obj[$k] : null;
	}

	protected function unsetProperty($k) {
		unset($this->obj[$k]);
		if ($this->new) {
			return $this;
		}
		if (!isset($this->update['$unset'])) {
			$this->update['$unset'] = [$k => 1];
		}
		$this->update['$unset'][$k] = 1;
		return $this;
	}

	protected function setProperty($k, $v) {
		$this->obj[$k] = $v;
		if ($this->new) {
			return;
		}
		if (!isset($this->update['$set'])) {
			$this->update['$set'] = [];
		}
		$this->update['$set'][$k] = $v;
		return $this;
	}

	public function __get($k) {
		return call_user_func([$this, 'get' . ucfirst($k)]);
	}
	public function __isset($k) {
		return call_user_func([$this, 'isset' . ucfirst($k)]);
	}
	public function __set($k, $v) {
		call_user_func([$this, 'set' . ucfirst($k)], $v);
	}
	public function __unset($k) {
		call_user_func([$this, 'unset' . ucfirst($k)]);
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
			$v = sizeof($args) ? $args[0] : null;
			return $this->setProperty($name, $v);
		}
		if (strncmp($method, 'unset', 5) === 0) {
			$name = lcfirst(substr($method, 5));
			return $this->unsetProperty($name);
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
		if ($this->cond === null) {
			if ($cb !== null) {
				call_user_func($cb, false);
			}
			return;
		}
		$this->countObject($cb);
	}

	abstract protected function countObject($cb);

	public function remove($cb) {
		if (!sizeof($this->cond)) {
			if ($cb !== null) {
				call_user_func($cb, false);
			}
			return;
		}
		$this->removeObject($cb);
		$this->new = true;
	}

	abstract protected function removeObject($cb);

	public function save($cb = null) {
		$this->lastError = [];
		if ($this->cond === null) {
			$this->extractCondFrom($this->obj);
		}
		if (!$this->new) {
			if (!sizeof($this->cond)) {
				if ($cb !== null) {
					call_user_func($cb, $this);
				}
				return;
			}
			if (!sizeof($this->update)) {
				if ($cb !== null) {
					call_user_func($cb, $this);
				}
				return;
			}
		}
		$this->saveObject(function($lastError) use ($cb) {
			$this->lastError = $lastError;
			if ($cb !== null) {
				call_user_func($cb, $this);
			}

		});
		$this->update = [];
		$this->new = false;
	}

	abstract protected function saveObject($cb);


	/**
	 * Checks if property exists
	 * @param string Property name
	 * @return boolean Exists?
	 */

	public function offsetExists($k) {
		return call_user_func([$this, 'get' . ucfirst($k)]) !== null;
	}

	/**
	 * Get property by name
	 * @param string Property name
	 * @return mixed
	 */
	public function offsetGet($k) {
		return call_user_func([$this, 'get' . ucfirst($k)]);;
	}

	/**
	 * Set property
	 * @param string Property name
	 * @param mixed  Value
	 * @return void
	 */
	public function offsetSet($k, $v) {
		call_user_func([$this, 'set' . ucfirst($k)], $v);
	}

	/**
	 * Unset property
	 * @param string Property name
	 * @return void
	 */
	public function offsetUnset($k) {
		call_user_func([$this, 'unset' . ucfirst($k)]);
	}
}

