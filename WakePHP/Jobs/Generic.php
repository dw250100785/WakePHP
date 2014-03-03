<?php
namespace WakePHP\Jobs;

use PHPDaemon\Core\Daemon;
use PHPDaemon\Core\Debug;

/**
 * Class Generic
 * @package WakePHP\Jobs
 * @dynamic_fields
 */
abstract class Generic {
	use \PHPDaemon\Traits\ClassWatchdog;

	protected $running = false;
	protected $instance;
	protected $parent;
	protected $progress;
	protected $exception;
	protected $status = 'a';
	protected $_id;
	public function __construct($job, $parent) {
		foreach ($job as $k => $v) {
			$this->{$k} = $v;
		}
		$this->parent = $parent;
	}


	/**
	 * Called when the request wakes up
	 * @return void
	 */
	public function onWakeup() {
		$this->running   = true;
		Daemon::$context = $this;
		Daemon::$process->setState(Daemon::WSTATE_BUSY);
	}

	/**
	 * Called when the request starts sleep
	 * @return void
	 */
	public function onSleep() {
		Daemon::$context = null;
		$this->running   = false;
		Daemon::$process->setState(Daemon::WSTATE_IDLE);
	}

	/**
	 * Uncaught exception handler
	 * @param $e
	 * @return boolean Handled?
	 */
	public function handleException($e) {
		$this->exception = $e;
		$this->setResult(false);
		return true;
	}

	abstract function run();

	public function updateProgress($progress, $cb = null) {
		if ($this->progress === $progress) {
			return;
		}
		$this->progress = $progress;
		$this->parent->jobqueue->updateProgress($this->_id, $progress, $this->status, $cb);

	}
	public function sendResult($result) {
		$this->status = $result !== false ? 's' : 'f';
		if ($this->status === 's') {
			$this->progress = 1;
		}
		$set = [
				'status' => $this->status,
				'progress' => $this->progress
			];
		if (isset($this->atmostonce)) {
			$set['atmostonce'] = new \MongoId; // @TODO: wait for mongodb fix
		}
		$this->parent->jobs->findAndModify([
			'query' => ['_id' => $this->_id, 'worker' => $this->parent->ipcId],
			'update' => ['$set' => $set],
			'new' => true,
		], function ($ret) use ($result) {
			if (!isset($ret['value'])) {
				return;
			}
			$this->instance = $ret['value']['instance'];
			$doc = [
				'_id'      => $this->_id,
				'ts'       => microtime(true),
				'instance' => $this->instance,
				'status'   => $this->status,
				'result'   => $result,
			];
			if ($this->exception instanceof \WakePHP\Exceptions\Generic) {
				$doc['exception'] = $this->exception->toArray();
			}
			$this->parent->jobresults->insert($doc);
		});
		$this->parent->unlinkJob($this->_id);
	}
}
