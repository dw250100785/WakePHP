<?php
namespace WakePHP\Core;

use PHPDaemon\Clients\Mongo\ConnectionFinished;
use PHPDaemon\Clients\Mongo\MongoId;
use PHPDaemon\Core\Daemon;
use PHPDaemon\Core\Debug;
use PHPDaemon\Core\Timer;
use PHPDaemon\Core\CallbackWrapper;

/**
 * JobManager class.
 */
class JobManager {
	use \PHPDaemon\Traits\ClassWatchdog;
	use \PHPDaemon\Traits\StaticObjectWatchdog;

	/**
	 * @var WakePHP
	 */
	public $appInstance;
	/**
	 * @var array
	 */
	public $callbacks = array();
	protected $pubSubMode = 'redis';

	/**
	 * @param WakePHP $appInstance
	 */
	public function __construct($appInstance) {
		$this->appInstance = $appInstance;
		$this->init();
	}

	/**
	 *
	 */
	public function init() {
		$this->appInstance->redis->subscribe($this->appInstance->config->redisprefix->value . 'jobFinished:'.$this->appInstance->ipcId, function($redis) {
			if (!$redis) {
				return;
			}
			$jobId = $redis->result[2];
			if (!isset($this->callbacks[$jobId])) {
				return;
			}
			$this->appInstance->jobqueue->getJobById($jobId, function($job) use ($jobId) {
				if (!isset($this->callbacks[$jobId])) {
					return;
				}
				call_user_func($this->callbacks[$jobId], $job);
				unset($this->callbacks[$jobId]);
			});
		});
	}

	/**
	 * @param $cb
	 * @param $type
	 * @param $args
	 */
	public function enqueue($cb, $type, $args, $add = [], $pushcb = null) {
		$ts = microtime(true);
		return $this->appInstance->jobqueue->push($type, $args, $ts, $add, function ($job) use ($cb, $pushcb) {
			if ($cb !== NULL) {
				$this->callbacks[(string) MongoId::import($job->getId())] = CallbackWrapper::wrap($cb);
			}
			if ($pushcb !== null) {
				call_user_func($pushcb);
			}
		});
	}

	/*public function __call($job, $args) {
		if (($e = end($args)) && (is_array($e) || is_object($e)) && is_callable($e)) {
			$cb = array_pop($args);
		} else {
			$cb = null;
		}
		$this->enqueue($job, $args, $cb);
	}*/
}
