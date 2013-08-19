<?php
namespace WakePHP\Jobs;

/**
 * Class Generic
 * @package WakePHP\Jobs
 * @dynamic_fields
 */
abstract class Generic {
	use \PHPDaemon\Traits\ClassWatchdog;

	protected $instance;
	protected $parent;
	protected $progress;
	protected $status = 'a';
	protected $_id;
	public function __construct($job, $parent) {
		foreach ($job as $k => $v) {
			$this->{$k} = $v;
		}
		$this->parent = $parent;
	}

	abstract function run();

	public function updateProgress($progress, $cb = null) {
		$this->progress = $progress;
		$this->parent->jobqueue->updateProgress($this->_id, $progress, $this->status, $cb);

	}
	public function sendResult($result) {
		$this->status = $result !== false ? 's' : 'f';
		if ($this->status === 's') {
			$this->progress = 1;
		}
		$this->parent->jobqueue->update(
			['_id' => $this->_id],
			['$set' => ['status' => $this->status, 'progress' => $this->progress]]
		);
		$this->parent->jobresults->insert([
					  '_id'      => $this->_id,
					  'ts'       => microtime(true),
					  'instance' => $this->instance,
					  'status'   => $this->status,
					  'result'   => $result
		]);
		$this->parent->unlinkJob($this->_id);
	}
}
