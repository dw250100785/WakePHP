<?php
namespace WakePHP\Objects;
use PHPDaemon\Exceptions\UndefinedMethodCalled;
use WakePHP\Exceptions\WrongCondition;
use WakePHP\Exceptions\WrongState;
use PHPDaemon\Clients\Mongo\MongoId;
use PHPDaemon\Core\Daemon;
use PHPDaemon\Core\Debug;
use PHPDaemon\Core\ClassFinder;
use PHPDaemon\Structures\StackCallbacks;

/**
 * Class Generic
 * @package WakePHP\Objects
 */
abstract class Generic implements \ArrayAccess {
	use \PHPDaemon\Traits\StaticObjectWatchdog;
	
	protected $orm;

	protected $iteratorClass = '\WakePHP\Objects\GenericIterator';

	protected static $ormName;

	protected $update = [];

	protected $upsertMode = false;

	protected $obj;

	protected $cond;

	protected $describe = [];

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

	protected $onSave;

	protected $onBeforeSave;

	protected $preventDefault = false;

	protected $writeConcerns = null;

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
	public function bulk() {
		return new GenericBulk($this, $this->col);
	}
	public function describe($m = null, $n = null) {
		$c = func_num_args();
		if ($c === 1) {
			if (is_array($m)) {
				foreach ($m as $k => $v) {
					$this->describe[$k] = $v;
				}
				return $this;
			}
			return $this->describe[$m];
		} elseif ($c === 2) {
			$this->describe[$m] = $n;
			return $this;
		}
		return $this->describe;
	}

	protected function onSave($cb) {
		if ($this->onSave === null) {
			$this->onSave = new StackCallbacks;
		}
		$this->onSave->push($cb);
	}

	protected function onBeforeSave($cb) {
		if ($this->onBeforeSave === null) {
			$this->onBeforeSave = new StackCallbacks;
		}
		$this->onBeforeSave->push($cb);
	}

	protected function safeMode($mode) {
		$this->safeMode = (bool) $mode;
		return $this;
	}

	public function beenUpdated() {
		return sizeof($this->update) > 0;
	}

	public static function _getORM($appInstance) {
		$k = lcfirst(static::$ormName);
		return isset($appInstance->{$k}) ? $appInstance->{$k} : null;
	}

	public static function ormInit($orm) {
	}

	protected function log($m) {
		Daemon::log(get_class($this).': ' . $m);
	}

	public function condSet($k, $v, $d = null) {
		$this->cond[$k] = $v;
		if ($d !== null) {
			$this->describe[$k] = $d;
		}
		return $this;
	}

	public function fields($fields) {
		$this->fields = $fields;
		return $this;
	}

	public function fetchUpdatedValue($param) {
		if (isset($this->obj[$param])) {
			return $this->obj[$param];
		}
		if (isset($this->update['$set'][$param])) {
			return $this->update['$set'][$param];
		}
		return null;
	}

	public function condSetId($id) {
		if (func_num_args() === 2) {
			$k = func_get_arg(0);
			$id = func_get_arg(1);
		} else {
			$k = '_id';
		}
		$id = MongoId::import($s = $id);
		if (!$id) {
			throw new WrongCondition('condSetId: wrong value: '.$s);
		}
		$this->cond[$k] = $id;
		return $this;
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
				$order = -1;
				if (strncmp($i, '>', 1)) {
					$i = substr($i, 1);
					$order = 1;
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

	public function limit($limit = null, $offset = null) {
		if (func_num_args() === 0) {
			return $this->limit;
		}
		$this->limit = $limit;
		$this->describe['limit'] = $limit;
		if ($offset !== null) {
			$this->offset = $offset;
			$this->describe['offset'] = $offset;
		}
		return $this;
	}

	public function offset($offset = 0) {
		if (func_num_args() === 0) {
			return $this->offset;
		}
		$this->offset = $offset;
		$this->describe['offset'] = $offset;
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
		$msg .= "OBJ: ".Debug::dump($this->obj) . "\n";
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
			$v = preg_split('~\s*,\s*~', $v);
		}
		return $this->set($k, array_filter($v, function($i) {return $i !== '';}));
	}

	public function uniqListSet($k, $v) {
		if (is_string($v)) {
			$v = preg_split('~\s*,\s*~', $v);
		}
		return $this->set($k, array_unique(array_filter($v, function($i) {return $i !== '';})));
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

	protected function &_getObjEntry($k) {
		$e =& $this->obj;
		foreach (explode('.', $k) as &$kk) {
			if ($kk === '$' || $k === '') {
				return null;
			}
			$e =& $e[$kk];
		}
		return $e;
	}

	protected function set($k, $v) {
		if ($this->obj !== null) {
			if (strpos($k, '.') !== false) {
				$entry = &$this->_getObjEntry($k);
			} else {
				$entry = &$this->obj[$k];
			}
			$entry = $v;
		}
		if ($this->new && !$this->upsertMode) {
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
		if ($this->obj !== null) {
			if (strpos($k, '.') !== false) {
				$entry = &$this->_getObjEntry($k);
			} else {
				$entry = &$this->obj[$k];
			}
			if ($entry === null) {
				$entry = $v;
			} else {
				$entry += $v;
			}
		}
		if ($this->new && !$this->upsertMode) {
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
		if ($this->obj !== null) {
			if (strpos($k, '.') !== false) {
				$entry = &$this->_getObjEntry($k);
			} else {
				$entry = &$this->obj[$k];
			}
			if ($entry === null) {
				$entry = -$v;
			} else {
				$entry -= $v;
			}
		}
		if ($this->new && !$this->upsertMode) {
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
		if ($this->obj !== null) {
			if (is_array($v) && isset($v['$each'])) {
				foreach ($v['$each'] as $vv) {
					$this->push($k, $vv);
				}
				return $this;
			}
			if (strpos($k, '.') !== false) {
				$entry = &$this->_getObjEntry($k);
			} else {
				$entry = &$this->obj[$k];
			}
			if ($entry === null) {
				$entry = [$v];
			} else {
				$entry[] = $v;
			}
		}
		if ($this->new && !$this->upsertMode) {
			return $this;
		}
		if (!isset($this->update['$push'][$k]['$each'])) {
			if (!isset($this->update['$push'])) {
				$this->update['$push'] = [$k => ['$each' => [$v]]];
			}
			else {
				$this->update['$push'][$k] = ['$each' => [$v]];
			}
		} else {
			$this->update['$push'][$k]['$each'][] = $v;
		}
		return $this;
	}

	protected function pull($k, $v) {
		if ($this->obj !== null) {
			if (is_array($v) && isset($v['$each'])) {
				foreach ($v['$each'] as $vv) {
					$this->pull($k, $vv);
				}
				return $this;
			}
			if (strpos($k, '.') !== false) {
				$entry = &$this->_getObjEntry($k);
			} else {
				$entry = &$this->obj[$k];
			}
			if ($entry !== null) {
				$entry = array_diff_key($entry, [$v => null]);
			}
		}
		if ($this->new && !$this->upsertMode) {
			return $this;
		}
		if (!isset($this->update['$pull'][$k]['$each'])) {
			if (!isset($this->update['$pull'])) {
				$this->update['$pull'] = [$k => ['$each' => [$v]]];
			}
			else {
				$this->update['$pull'][$k] = ['$each' => [$v]];
			}
		} else {
			$this->update['$pull'][$k]['$each'][] = $v;
		}
		return $this;
	}

	protected function addToSet($k, $v) {
		if ($this->obj !== null) {
			if (is_array($v) && isset($v['$each'])) {
				foreach ($v['$each'] as $vv) {
					$this->addToSet($k, $vv);
				}
				return $this;
			}
			if (strpos($k, '.') !== false) {
				$entry = &$this->_getObjEntry($k);
			} else {
				$entry = &$this->obj[$k];
			}
			if ($entry === null) {
				$entry = [$v];
			} else {
				if (!in_array($v, $entry, true)) {
					$entry[] = $v;
				}
			}
		}
		if ($this->new && !$this->upsertMode) {
			return $this;
		}
		if (!isset($this->update['$addToSet'][$k]['$each'])) {
			if (!isset($this->update['$addToSet'])) {
				$this->update['$addToSet'] = [$k => ['$each' => [$v]]];
			}
			else {
				$this->update['$addToSet'][$k] = ['$each' => [$v]];
			}
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

	public function extractCond() {
		$this->extractCondFrom($this->obj);
		return $this;
	}

	protected function cond($cond = null) {
		if (!func_num_args()) {
			return $this->cond;
		}
		$this->cond = $cond;
		return $this;
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
		return isset($this->obj['_id']) ? $this->obj['_id'] : (isset($this->cond['_id']) ? $this->cond['_id'] : null);
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
		if ($this->obj !== null) {
			unset($this->obj[$k]);
			if ($this->new && !$this->upsertMode) {
				return $this;
			}
		}
		if (!isset($this->update['$unset'])) {
			$this->update['$unset'] = [$k => 1];
		}
		$this->update['$unset'][$k] = 1;
		return $this;
	}

	protected function setProperty($k, $v) {
		if ($this->obj !== null) {
			$this->obj[$k] = $v;
		}
		if ($this->new && !$this->upsertMode) {
			return $this;
		}
		if (!isset($this->update['$set'])) {
			$this->update['$set'] = [];
		}
		$this->update['$set'][$k] = $v;
		return $this;
	}

	public function _clone() {
		$o = clone $this;
		$o->_cloned();
		return $o;
	}

	public function _cloned() {
		$this->new = true;
		$this->obj['_id'] = new \MongoId;
	}

	/**
	 * @param string $method
	 * @param array $args
	 * @return null|mixed
	 */
	public function __call($method, $args) {
		if ($method === 'clone') {
			return $this->_clone();
		}
		if (strncmp($method, 'get', 3) === 0) {
			return $this->getProperty(lcfirst(substr($method, 3)));
		}
		if (strncmp($method, 'set', 3) === 0) {
			return $this->setProperty(lcfirst(substr($method, 3)), sizeof($args) ? $args[0] : null);
		}
		if (strncmp($method, 'push', 4) === 0) {
			return $this->push(lcfirst(substr($method, 4)), sizeof($args) ? $args[0] : null);
		}
		if (strncmp($method, 'pull', 4) === 0) {
			return $this->pull(lcfirst(substr($method, 4)), sizeof($args) ? $args[0] : null);
		}
		if (strncmp($method, 'addToSet', 8) === 0) {
			return $this->addToSet(lcfirst(substr($method, 8)), sizeof($args) ? $args[0] : null);
		}
		if (strncmp($method, 'is', 2) === 0) {
			return (bool) $this->getProperty(lcfirst(substr($method, 2)));
		}
		if (strncmp($method, 'unset', 5) === 0) {
			return $this->unsetProperty(lcfirst(substr($method, 5)));
		}
		if (strncmp($method, 'touch', 5) === 0) {
			return $this->touch(lcfirst(substr($method, 5)), sizeof($args) ? $args[0] : null, sizeof($args) > 2 ? $args[1] : null);
		}
		if (strncmp($method, 'microtouch', 10) === 0) {
			return $this->microtouch(lcfirst(substr($method, 10)), sizeof($args) ? $args[0] : null, sizeof($args) > 2 ? $args[1] : null);
		}
		throw new UndefinedMethodCalled('Call to undefined method ' . get_class($this) . '->' . $method);
	}

	public function touch($k, $val = null, $ifUpdated = false ) {
		if ($ifUpdated && !$this->beenUpdated()) {
			return $this;
		}
		if ($val === null) {
			$val = time();
		}
		return $this->set($k, $val);
	}

	public function microtouch($k, $val = null, $ifUpdated = false) {
		if ($ifUpdated && !$this->beenUpdated()) {
			return $this;
		}
		if ($val === null) {
			$val = microtime(true);
		}
		return $this->set($k, $val);
	}
	public function fetchMulti($cb, $all = true) {
		return $this->multi()->fetch($cb, $all);
	}
	public function fetchOnce($cb, $all = true) {
		if ($this->obj !== null) {
			call_user_func($cb, $this);
		} else {
			$this->fetch($cb, $all);
		}

		return $this;
	}
	public function iterator() {
		$class = $this->iteratorClass;
		return new $class($this, $cb, $this->orm); // @TODO: check class
	}
	public function fetch($cb, $all = true) {
		if ($cb === null) {
			return $this;
		}
		if ($this->multi) {
			$class = $this->iteratorClass;
			$list = new $class($this, $cb, $this->orm); // @TODO: check class
			$this->fetchObject(function($cursor) use ($list, $all) {
				$list->_cursor($cursor, $all);
			});
		} else {
			$this->fetchObject(function($obj) use ($cb) {
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
		}
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
			$params = [
				'where' => $this->cond,
				'sort' => $this->sort,
				'offset' => $this->offset,
				'limit' => $this->limit,
				'fields' => $this->fields,
			];
			if (!is_array($params['sort'])) {
				unset($params['sort']);
			}
			$this->col->find($cb, $params);
		} else {
			$this->col->findOne($cb, [
				'where' => $this->cond,
				'sort' => $this->sort,
				'fields' => $this->fields,
			]);
		}
	}

	public function count($cb) {
		if ($this->cond === null) {
			if ($cb !== null) {
				call_user_func($cb, $this, false);
			}
			return $this;
		}
		$this->countObject(function ($res) use ($cb) {
			call_user_func($cb, $this, isset($res['n']) ? $res['n'] : false);
		});
		return $this;
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

	public function updateWithCond($addCond, $update, $cb = null) {
		$this->lastError = [];
		if ($this->cond === null) {
			$this->extractCondFrom($this->obj);
		}
		$oldCond = $this->cond;
		if (is_array($this->cond) && is_array($addCond)) {
			$this->cond += $addCond;
		}
		if ($this->new) {
			throw new WrongState('unable to update new object');
		}
		if ($this->cond === null) {
			if ($cb !== null) {
				call_user_func($cb, $this);
			}
			return $this;
		}
		if (!sizeof($update)) {
			if ($cb !== null) {
				call_user_func($cb, $this);
			}
			return $this;
		}
		$oldUpdate = $this->update;
		$this->update = $update;
		$this->saveObject($cb === null ? null : function($lastError) use ($cb) {
			$this->lastError = $lastError;
			if ($cb !== null) {
				call_user_func($cb, $this);
			}
			$this->lastError = [];
		});
		$this->update = $oldUpdate;
		$this->cond = $oldCond;
		return $this;
	}

	protected function preventDefault() {
		$this->preventDefault = true;
	}

	public function save($cb = null, GenericBulk $bulk = null) {
		$this->lastError = [];
		if ($this->cond === null) {
			$this->extractCondFrom($this->obj);
		}
		if (!$this->new) {
			if ($this->cond === null) {
				if ($cb !== null) {
					call_user_func($cb, $this);
				}
				return $this;
			}
			if (!sizeof($this->update)) {
				if ($cb !== null) {
					call_user_func($cb, $this);
				}
				return $this;
			}
		}
		if ($this->onBeforeSave !== null) {
			$this->onBeforeSave->executeAll($this);
		}
		if ($this->preventDefault) {
			if ($cb !== null) {
				$this->onSave($cb);
			}
			$this->preventDefault = false;
			return $this;
		}
		$w = $cb === null ? null : function($lastError) use ($cb) {
			$this->lastError = $lastError;
			if ($cb !== null) {
				call_user_func($cb, $this);
			}
			if ($this->onSave !== null) {
				$this->onSave->executeAll($this);
			}
			$this->lastError = [];
		};
		if ($bulk !== null) {
			$bulk->add($this->obj, $w, $this);
		} else {
			$this->saveObject($w);
		}
		$this->update = [];
		$this->new = false;
		return $this;
	}
	protected function saveObject($cb) {
		if ($this->new) {
			if ($this->upsertMode) {
				$this->obj['_id'] = $this->col->upsertOne($this->cond, $this->update, $cb, $this->writeConcerns);
			} else {
				$this->obj['_id'] = $this->col->insertOne($this->obj, $cb, $this->writeConcerns);
			}
		} else {
			if ($this->multi) {
				$this->col->updateMulti($this->cond, $this->update, $cb, $this->writeConcerns);
			} else {
				if ($this->upsertMode) {
					$this->col->upsertOne($this->cond, $this->update, $cb, $this->writeConcerns);
				} else {
					$this->col->updateOne($this->cond, $this->update, $cb, $this->writeConcerns);
				}
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

	public function setId($v) {
		return $this->set('_id', $v);
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

