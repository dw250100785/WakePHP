<?php
namespace WakePHP\Objects;
use PHPDaemon\Exceptions\UndefinedMethodCalled;
use PHPDaemon\Core\Daemon;
use PHPDaemon\Core\Debug;

/**
 * Class GenericIterator
 * @package WakePHP\Objects
 */
class GenericIterator implements \Iterator {
	use \PHPDaemon\Traits\StaticObjectWatchdog;
	protected $obj;
	protected $cursor;
	protected $cb;
	protected $orm;
	protected $modeAll = false;
	protected $limit = null;
	protected $finished = false;
	
	public function __construct($obj, $cb, $orm) {
		$this->cb = $cb;
		$this->obj = $obj;
		$this->orm = $orm;
	}

	public function iterator() {
		return $this;
	}
	public function fetch($cb) {
		call_user_func($cb, $this);
		return $this;
	}

	public function setModeAll($bool = true) {
		$this->modeAll = $bool;
		return $this;
	}

	public function setLimit($n) {
		$this->limit = $n;
		return $this;
	}

	public function keep($bool = true) {
		$this->cursor->keep($bool);
		return $this;
	}

	public function __invoke($cursor) {
		if ($this->cursor === null) {
			$this->cursor = $cursor;
		}
		if ($this->modeAll && !$this->finished()) {
			$this->more();
			return;
		}
		$this->cb === null || call_user_func($this->cb, $this);
		if ($this->finished()) {
			$this->cb = null;		
		}
	}
	
	/**
	 * @param string $method
	 * @param array $args
	 * @return null|mixed
	 */
	public function __call($method, $args) {
		return call_user_func_array([$this->obj, $method], $args);
	}

	public function more($n = 0) {
		if ($this->limit !== null) {
			if ($this->cursor->counter <= $this->limit) {
				$this->cursor->getMore($this->limit - $this->cursor->counter);
			}
		} else {
			$this->cursor->getMore($n);
		}
		return $this;
	}
	
	public function current() {
		$o = $this->cursor->current();
		if (is_array($o)) {
			$class = get_class($this->obj);
			$r = new $class(null, null, $this->orm);
			return $r->wrap($o);
		}
		return $o;
	}

	public function finished() {
		if ($this->limit !== null) {
			if ($this->cursor->counter >= $this->limit) {
				return true;
			}
		}
		return $this->cursor->isFinished();
	}

	public function toArray() {
		$arr = [];
		foreach ($this as $item) {
			$arr[] = $item->toArray();
		}
		return $arr;
	}

	public function key() {
		return $this->cursor->key();
	}

	public function next() {
		$this->cursor->next();
	}

	public function rewind() {
		$this->cursor->rewind();
	}

	public function valid() {
		return $this->cursor->valid();
	}
}

