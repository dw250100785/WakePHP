<?php
namespace WakePHP\Objects;
use PHPDaemon\Exceptions\UndefinedMethodCalled;
use WakePHP\Exceptions\WrongCondition;
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

	protected $insert;

	protected $obj;

	protected $cond;

	protected $new = false;

	protected $inited = false;

	protected $appInstance;

	protected $lastError;

	protected $fields;

	protected $col;

	protected $multi = false;

	protected $limit = null;
	
	protected $sort;

	protected $offset = 0;

	protected $protectedCall;

	protected $safeMode = true;

	public function __construct($cond, $objOrCb, $orm) {
		$this->orm = $orm;
		$this->appInstance = $orm->appInstance;
		$this->cond = $cond instanceof \MongoId ? ['_id' => $cond] : $cond;
		$this->construct();
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

	protected function safeMode($mode) {
		$this->safeMode = (bool) $mode;
		return $this;
	}

	protected function log($m) {
		Daemon::log(get_class($this).': ' . $m);
	}

	public function condSet($k, $v) {
		$this->cond[$k] = $v;
	}

	public function fields($fields) {
		$this->fields = $fields;
		return $this;
	}

	public function condSetId($id) {
		if (func_num_args() === 2) {
			$k = func_get_arg(0);
			$id = func_get_arg(1);
		} else {
			$k = '_id';
		}

		if ($id instanceof \MongoId) {
			$this->cond[$k] = $id;
			return $this;
		}
		if (is_string($id) && ctype_xdigit($id) && strlen($id) === 24) {
			$this->cond[$k] = new \MongoId($id);
			return $this;
		}
		throw new WrongCondition('condSetId: wrong value');
	}

	public function multi() {
		$this->multi = true;
		return $this;
	}

	public function sort($sort) {
		$this->sort = $sort;
		return $this;
	}

	public function sortMixed($mixed, $fields = null, $multi = true) {
		$this->sort = [];
		if (!is_array($mixed)) {
			$mixed = explode(',', $mixed);
		}
		if (isset($mixed[0])) {
			foreach ($mixed as $i) {
				$i = trim($i);
				$order = 1;
				if (strncmp($i, '>', 1)) {
					$i = substr($i, 1);
					$order = -1;
				} elseif (strncmp($i, '<', 1)) {
					$i = substr($i, 1);
				}
				if ($fields !== null) {
					if (!in_array($i, $fields, true)) {
						continue;
					}
				}
				$this->sort[$i] = $order;
			}
		} else {
			foreach ($mixed as $i => $order) {
				$order = $order ? 1 : -1;
				if ($fields !== null) {
					if (!in_array($i, $fields, true)) {
						continue;
					}
				}
				$this->sort[$i] = $order;
			}
		}
		return $this;
	}

    public function toJSON($flags = null) {
    	if ($flags === null) {
    		$flags = JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE;
    	}
    	return json_encode($this->toArray(), $flags);

    }

	public function limit($limit, $offset = null) {
		$this->limit = $limit;
		if ($offset !== null) {
			$this->offset = $offset;
		}
		return $this;
	}

	public function offset($offset) {
		$this->offset = $offset;
		return $this;
	}

	protected function protectedCall() {
		$this->protectedCall = true;
		$args = func_get_args();
		call_user_func_array([$this, array_shift($args)], $args);
		$this->protectedCall = false;

	}
	
	protected function construct() {}

	public function debug($point = null) {
		$msg = "\n--------------------------\n";
		$msg .= "TYPE: " . get_class($this) . "\n";
		$msg .= "NEW: " . ($this->new ? 'YES' : 'NO') . "\n";
		$msg .= "POINT: ".Debug::dump($point) . "\n";
		$msg .= "COND: ".Debug::dump($this->cond) . "\n";
		$msg .= "UPDATE: ".Debug::dump($this->update) . "\n";
		$msg .= "--------------------------";
		Daemon::log($msg);
		return $this;
	}
	public function toArray() {
		return $this->obj;
	}

	public function fromArray($arr) {
		return $this->create($arr);

	}

	public function listSet($k, $v) {
		if (is_string($v)) {
			$v = array_filter(function($i) {return $i === '';}, preg_split('~\s*,\s*~', $v));
		}
		$this->set($k, $v);
	} 
	
	public function lastError($bool = false) {
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
			return $this;
		}
		if (!isset($this->update['$set'])) {
			$this->update['$set'] = [$k => $v];
		} else {
			$this->update['$set'][$k] = $v;
		}
		return $this;
	}

	protected function inc($k, $v = 1) {
		if (!isset($this->obj[$k])) {
			$this->obj[$k] = $v;
		} else {
			$this->obj[$k] += $v;
		}
		if ($this->new) {
			return $this;
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
		return $this;
	}

	protected function dec($k, $v = 1) {
		if (!isset($this->obj[$k])) {
			$this->obj[$k] = -$v;
		} else {
			$this->obj[$k] -= $v;
		}
		if ($this->new) {
			return $this;
		}
		if (!isset($this->update['$inc'])) {
			$this->update['$inc'] = [$k => -$v];
		} else {
			if (!isset($this->update['$inc'][$k])) {
				$this->update['$inc'][$k] = -$v;
			} else {
				$this->update['$inc'][$k] -= $v;
			}
		}
		return $this;
	}

	protected function push($k, $v) {
		if (isset($v['$each'])) {
			foreach ($v['$each'] as $vv) {
				$this->push($k, $vv);
			}
			return $this;
		}
		if (!isset($this->obj[$k])) {
			$this->obj[$k] = [$v];
		} else {
			$this->obj[$k][] = $v;
		}
		if ($this->new) {
			return $this;
		}
		if (!isset($this->update['$push'][$k]['$each'])) {
			if (!isset($this->update['$push'])) {
				$this->update['$push'] = [$k => ['$each' => [$v]]];
			}
			else {
				$this->update['$push'][$k] = ['$each' => [$v]];
			}
			$this->update['$push'][$k] ['$each'] = [$v];
		} else {
			$this->update['$push'][$k]['$each'][] = $v;
		}
		return $this;
	}

	protected function addToSet($k, $v) {
		if (isset($v['$each'])) {
			foreach ($v['$each'] as $vv) {
				$this->addToSet($k, $vv);
			}
			return $this;
		}
		if (!isset($this->obj[$k])) {
			$this->obj[$k] = [$v];
		} else {
			if (in_array($v, $this->obj[$k], true)) {
				return $this;
			}
			$this->obj[$k][] = $v;
		}
		if ($this->new) {
			return $this;
		}
		if (!isset($this->update['$addToSet'][$k]['$each'])) {
			if (!isset($this->update['$addToSet'])) {
				$this->update['$addToSet'] = [$k => ['$addToSet' => [$v]]];
			}
			else {
				$this->update['$addToSet'][$k] = ['$addToSet' => [$v]];
			}
			$this->update['$addToSet'][$k] ['$each'] = [$v];
		} else {
			$this->update['$addToSet'][$k]['$each'][] = $v;
		}
		return $this;
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

	public function wrap($obj) {
		$this->obj = $obj;
		$this->extractCondFrom($obj);
		$this->inited = true;
		$this->init();
		return $this;
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
				return $this;
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
			return $this;
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
		if (strncmp($method, 'is', 2) === 0) {
			$name = lcfirst(substr($method, 2));
			return (bool) $this->getProperty($name);
		}
		if (strncmp($method, 'unset', 5) === 0) {
			$name = lcfirst(substr($method, 5));
			return $this->unsetProperty($name);
		}
		throw new UndefinedMethodCalled('Call to undefined method ' . get_class($this) . '->' . $method);
	}

	public function fetch($cb, $all = true) {
		$list = new GenericIterator($this, $cb, $this->orm);
		$this->fetchObject($this->multi ? function($cursor) use ($list, $all) {
			$list->_cursor($cursor, $all);
		} : function($obj) use ($cb) {
			$this->obj = $obj;
			if ($obj === false) {
				call_user_func($cb, $this);
				return;
			}
			$this->new = false;
			if (!$this->inited) {
				$this->inited = true;
				$this->init();
			}
			call_user_func($cb, $this);
		});
		return $this;
	}

	public function exists() {
		return is_array($this->obj) ? true : ($this->obj === null ? null : false);
	}

	protected function fetchObject($cb) {
		if ($this->col === null) {
			Daemon::log(get_class($this). Debug::backtrace());
		}
		if ($this->multi) {
			$this->col->find($cb, $params = [
				'where' => $this->cond,
				'sort' => $this->sort,
				'offset' => $this->offset,
				'limit' => $this->limit,
				'fields' => $this->fields,
			]);
		} else {
			$this->col->findOne($cb, [
				'where' => $this->cond,
				'fields' => $this->fields,
			]);
		}
	}

	public function count($cb) {
		if ($this->cond === null) {
			if ($cb !== null) {
				call_user_func($cb, $this, false);
			}
			return;
		}
		$this->countObject(function ($res) use ($cb) {
			call_user_func($cb, $this, isset($res['n']) ? $res['n'] : false);
		});
	}

	protected function countObject($cb) {
		if ($this->cond === null) {
			if ($cb !== null) {
				call_user_func($cb, false);
			}
			return;
		}
		$this->col->count($cb, ['where' => $this->cond]);
	}

	public function remove($cb) {
		if ($this->cond === null) {
			if ($cb !== null) {
				call_user_func($cb, false);
			}
			return;
		}
		if ($this->safeMode) {
			if (!sizeof($this->cond)) {
				$this->log('safe-mode: attempt to remove() with empty conditions');
				call_user_func($cb, $this, false);	
				return;
			}
		}
		$this->removeObject($cb === null ? null : function($lastError) use ($cb) {
			$this->lastError = $lastError;
			if ($cb !== null) {
				call_user_func($cb, $this);
			}
		});
		$this->new = true;
	}

	protected function removeObject($cb) {
		if ($this->cond === null) {
			if ($cb !== null) {
				call_user_func($cb, false);
			}
			return;
		}
		$this->col->remove($this->cond, $cb);
	}

	public function update($cb = null) {
		$this->lastError = [];
		if ($this->cond === null) {
			$this->extractCondFrom($this->obj);
		}
		$this->new = false;
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
		$this->saveObject($cb === null ? null : function($lastError) use ($cb) {
			$this->lastError = $lastError;
			if ($cb !== null) {
				call_user_func($cb, $this);
			}
			$this->lastError = [];
		});
		$this->update = [];
	}

	public function save($cb = null) {
		$this->lastError = [];
		if ($this->cond === null) {
			$this->extractCondFrom($this->obj);
		}
		if (!$this->new) {
			if ($this->cond === null) {
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
		$this->saveObject($cb === null ? null : function($lastError) use ($cb) {
			$this->lastError = $lastError;
			if ($cb !== null) {
				call_user_func($cb, $this);
			}
			$this->lastError = [];
		});
		$this->update = [];
		$this->new = false;
	}

	protected function saveObject($cb) {
		if ($this->new) {
			$this->col->insertOne($this->obj, $cb);
		} else {
			if ($this->multi) {
				$this->col->updateMulti($this->cond, $this->update, $cb);
			} else {
				$this->col->updateOne($this->cond, $this->update, $cb);
			}
		}
	}


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

