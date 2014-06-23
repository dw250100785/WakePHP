<?php
namespace WakePHP\Objects\Jobqueue;

use PHPDaemon\Core\Daemon;
use PHPDaemon\Core\Debug;
use PHPDaemon\Clients\Mongo\MongoId;

/**
 * Class Generic
 * @package WakePHP\Jobs
 * @dynamic_fields
 */
class Job extends \WakePHP\Objects\Generic {
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
					$this->cond = [
						'atmostonce' => $this['atmostonce'],
						'status' => ['$in' => ['a', 'v']],
						'notbefore' => ['$lte' => $this['notbefore']],
					];
					$this->findAndModifyMode = true;
					$this->upsertMode = true;
				} else {
					$this->findAndModifyMode = false;
					$this->upsertMode = false;
				}
				$this->onSave(function() {
					$this->findAndModifyMode = true;
					$this->upsertMode = true;
					$this->orm->appInstance->redis->publish($this->orm->appInstance->config->redisprefix->value  . 'jobEnqueuedSig', 1);
				});
				$this->addToSet('instance', $this->orm->appInstance->ipcId);
			});
		}
	}

	public static function ormInit($orm) {
		parent::ormInit($orm);
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
		$this['status'] = $result !== false ? 's' : 'f';
		if ($this['status'] === 's') {
			$this->progress = 1;
		}
		$this['result'] = $result;
		$this->cond = ['_id' => $this->getId(), 'worker' => $this->orm->appInstance->ipcId];
		$this->save(function() {
			$id = (string) MongoId::import($this->getId());
			foreach ($this['instance'] as $i) {
				$this->orm->appInstance->redis->publish($this->orm->appInstance->config->redisprefix->value . 'jobFinished:'.MongoId::import($i), $id);
			}
		});
		$this->orm->appInstance->unlinkJob($this);
	}
}
