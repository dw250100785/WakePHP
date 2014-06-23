<?php
namespace WakePHP\Core;

use PHPDaemon\Clients\Mongo\Collection;
use PHPDaemon\Clients\Mongo\ConnectionFinished;
use PHPDaemon\Clients\Mongo\MongoId;
use PHPDaemon\Core\AppInstance;
use PHPDaemon\Core\Daemon;
use PHPDaemon\Core\Debug;
use PHPDaemon\Core\CallbackWrapper;
use PHPDaemon\Core\Timer;
use WakePHP\ORM\Sessions;

/**
 * Job worker
 * @dynamic_fields
 */
class JobWorker extends WakePHP {
	/** @var  Collection */
	public $jobqueue;
	public $jobresults;
	protected $tryEvent;
	protected $perworker = [];
	public $components;
	protected $runningJobs = [];
	protected $maxRunningJobs = 100;
	public $jobs;
	protected $jobtypes = [];
	protected $wtsEvent;

	/**
	 *
	 */
	public function init() {
		parent::init();
		$this->httpclient = \PHPDaemon\Clients\HTTP\Pool::getInstance(['timeout' => 1]);
		$this->components = new Components($this->fakeRequest());
	}
	protected function tryToAcquire() {
		if (sizeof($this->runningJobs) >= $this->maxRunningJobs) {
			return;
		}
		$q = [
			'$and' => [['$or' => [
				['status' => 'v'],
				['status' => 'a', 'wts' => ['$lt' => microtime(true) - 5]],
			]]],
			'notbefore' => ['$lte' => time()],
			//'type' => ['$in' => $types],
			'shardId' => ['$in' => isset($this->config->shardid->value) ? [null, $this->config->shardid->value] : [null]],
	   		'serverId' => ['$in' => isset($this->config->serverid->value) ? [null, $this->config->serverid->value] : [null]],
		];
		foreach ($this->perworker as $k => $v) {
			$q['$and'][] = [
				'$or' => [
					['perworker.'.$k => ['$lt' => $v]],
					['perworker.'.$k => null]
			]];
		}
		$this->jobqueue->jobs->findAndModify([
			'query' => $q,
			'update' => [
				'$set' => [
					'status' => 'a',
					'worker' => $this->ipcId,
					'wts' => microtime(true),
				],
				'$inc' => ['tries' => 1],
			],
			'sort' => ['priority' => 1],
			'new' => true,
			], function ($ret) use ($q) {
				if (!isset($ret['value'])) {
					return;
				}
				$job = $ret['value'];
				Daemon::log('Acquired job: ' . json_encode($job));
				$this->startJob($job);
				$this->tryToAcquire();
			}
		);
	}

	public function onReady() {
		parent::onReady();
		$this->wtsEvent = Timer::add(function ($event) {
			$this->jobqueue->jobs->updateMulti(['worker' => $this->ipcId, 'status' => 'a'], ['$set' => ['wts' => microtime(true)]]);
			$event->timeout();
		}, 2.5e6);
		$this->redis->subscribe($this->config->redisprefix->value . 'jobEnqueuedSig', function($redis) {
			Daemon::log('jobEnqueuedSig got');
			$this->tryToAcquire();
		}, function() {
			$this->tryToAcquire();
		});
		$this->tryEvent = Timer::add(function ($event) {
			/** @var Timer $event */
			$this->tryToAcquire();
			$event->timeout(1e6);
		}, 1e6);
		
	}

	protected function fakeRequest() {
		$req = new \stdClass;
		$req->appInstance = $this;
		return $req;
	}

	/**
	 * @param $job
	 */
	public function startJob($job) {
		$jobId = (string)$job['_id'];
		$class = '\\WakePHP\\Jobs\\' . $job['type'];
		if (!class_exists($class) || !is_subclass_of($class, '\\WakePHP\\Jobs\\Generic')) {
			$class = '\\WakePHP\\Jobs\\JobNotFound';
		}
		Daemon::log('startJob(' . $class . ')');
		$obj = new $class(null, null, $this->jobqueue);
		$obj->wrap($job);
		$this->runningJobs[$jobId] = $obj;
		if (isset($job['perworker']) && $job['perworker']) {
			foreach ($job['perworker'] as $k => $v) {
				if (!isset($this->perworker[$k])) {
					$this->perworker[$k] = 1;
				} else {
					++$this->perworker[$k];
				}
			}
		}
		$obj->run();
	}

	public function unlinkJob($job) {
		if ($job['perworker']) {
			foreach ($job['perworker'] as $k => $v) {
				if (isset($this->perworker[$k])) {
					--$this->perworker[$k];
					if ($this->perworker[$k] <= 0) {
						unset($this->perworker[$k]);
					}
				}
			}
		}
		unset($this->runningJobs[(string) $job['id']]);
		$this->tryToAcquire();
	}
}
