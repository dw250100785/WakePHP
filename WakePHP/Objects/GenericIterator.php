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
	
	public function __construct($obj, $cb, $orm) {
		$this->cb = $cb;
		$this->obj = $obj;
		$this->orm = $orm;
	}

	public function _cursor($cursor) {
		if ($this->cursor === null) {
			$this->cursor = $cursor;
		}
		call_user_func($this->cb, $this);
	}
	
	/**
	 * @param string $method
	 * @param array $args
	 * @return null|mixed
	 */
	public function __call($method, $args) {
		return call_user_func_array([$this->obj, $method], $args);
	}

	public function more() {
		$this->cursor->getMore();
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

