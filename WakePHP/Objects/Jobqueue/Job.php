<?php
namespace WakePHP\Objects\Jobqueue;

use PHPDaemon\Core\Daemon;
use PHPDaemon\Core\Debug;

/**
 * Class Generic
 * @package WakePHP\Jobs
 * @dynamic_fields
 */
class Job extends \WakePHP\Objects\Generic {
	protected $exception;
	protected $findAndModifyMode = true;
	protected $upsertMode = true;
	protected $progress = 0;

	protected function construct() {
		$this->col = $this->orm->jobs;
	}

	public function init() {
		if ($this->new) {
			$this->defaultSet('notbefore', 0)->set('status', 'v');
			$this->onBeforeSave(function() {
				if ($this['atmostonce'] !== null) {
					$this->cond = ['atmostonce' => $this['atmostonce'], 'status' => ['$in' => ['a', 'v']]];
					$this->findAndModifyMode = true;
					$this->upsertMode = true;
				} else {
					$this->findAndModifyMode = false;
					$this->upsertMode = false;
				}
				$this->onSave(function() {
					$this->findAndModifyMode = true;
					$this->upsertMode = true;
					$this->orm->appInstance->redis->publish('jobEnqueuedSig', 1);
				});
				$this->addToSet('instance', $this->orm->appInstance->ipcId);
			});
		}
	}

	public static function ormInit($orm) {
		parent::ormInit($orm);
		$orm->jobresults  = $orm->appInstance->db->{$orm->appInstance->dbname . '.jobresults'};
		$orm->jobqueue  = $orm->appInstance->db->{$orm->appInstance->dbname . '.jobqueue'};
		$orm->jobs  = $orm->appInstance->db->{$orm->appInstance->dbname . '.jobs'};
		$orm->jobs->ensureIndex(['status' => 1]);
		$orm->jobs->ensureIndex(['priority' => -1]);
		$orm->jobs->ensureIndex(['atmostonce' => 1]);
		$orm->jobs->ensureIndex(['worker' => 1]);
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
		if ($e instanceof \WakePHP\Exceptions\Generic) {
			$this['exception'] = $e->toArray();
		} else {
			$this['exception'] = (array) $e;
		}
		$this->setResult(false);
		return true;
	}

	public function run() {
	}

	public function updateProgress($progress, $cb = null) {
		if ($this['progress'] === $progress) {
			return;
		}
		$this['progress'] = $progress;
		$this->save($cb);

	}
	public function sendResult($result) {
		Daemon::log(get_class($this) . '->sendResult()');
		$this['status'] = $result !== false ? 's' : 'f';
		if ($this['status'] === 's') {
			$this->progress = 1;
		}
		$this['result'] = $result;
		$this->cond = ['_id' => $this->getId(), 'worker' => $this->orm->appInstance->ipcId];
			if ($this->exception instanceof \WakePHP\Exceptions\Generic) {
				$doc['exception'] = $this->exception->toArray();
			}
		$this->save(function() {
			$doc = [
				'_id'      => $this->getId(),
				'ts'       => microtime(true),
				'instance' => $this['instance'],
				'status'   => $this['status'],
				'result'   => $this['result'],
			];

			$this->orm->jobresults->insert($doc);
			foreach ($this['instance'] as $i) {
				$this->orm->appInstance->redis->publish('jobFinished:'.$i, 1);
			}
		});
		$this->orm->appInstance->unlinkJob($this);
	}
}
