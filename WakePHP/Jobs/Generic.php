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
		$this->parent->jobqueue->getCollection()->findAndModify([
			'query' => ['_id' => $this->_id],
			'update' => ['$set' => $set],
			'new' => true,
		], function ($ret) use ($result) {
			if (isset($ret['value'])) {
				$this->instance = $ret['value']['instance'];
			}
			$this->parent->jobresults->insert([
				'_id'      => $this->_id,
				'ts'       => microtime(true),
				'instance' => $this->instance,
				'status'   => $this->status,
				'result'   => $result
			]);
		});
		$this->parent->unlinkJob($this->_id);
	}
}
