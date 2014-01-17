<?php
namespace WakePHP\Objects;
use PHPDaemon\Exceptions\UndefinedMethodCalled;
use WakePHP\Exceptions\WrongCondition;
use PHPDaemon\Core\Daemon;
use PHPDaemon\Core\Debug;
use PHPDaemon\Core\ClassFinder;
use PHPDaemon\Structures\StackCallbacks;

/**
 * Class GenericBulk
 * @package WakePHP\Objects
 */
class GenericBulk {
	use \PHPDaemon\Traits\StaticObjectWatchdog;
	
	protected $col;

	protected $bulk;

	protected $callbacks;
	
	protected $writeConcerns;

	protected $lastError;

	public function __construct(Generic $obj, $col) {
		$this->obj = $obj;
		$this->col = $col;
		$this->bulk = new SplStack;
		$this->callbacks = new StackCallbacks;
	}

	public function add($doc, $cb = null) {
		$this->bulk->push($doc);
		$this->callbacks->push($cb);
	}

	public function count() {
		return $this->bulk->count();
	}

	public function run($cb = null, $max = -1) {
		$docs = [];
		$cbs = [];
		$n = 0;
		while (!$this->bulk->isEmpty()) {
			if ($max !== -1 && $n > $max) {
				return $n;
			}
			++$n;
			$docs[] = $this->bulk->shift();
		}
		$this->col->insertMulti($docs, function($lastError) use ($n, $cb) {
			$this->lastError = $lastError;
			for ($i = 0; $i < $n; ++$i) {
				$this->callbacks->executeOne($this);
			}
			if ($cb !== null) {
				call_user_func($cb, $this);
			}
		}, $this->writeConcerns);
		return $n;
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
}

