<?php
namespace WakePHP\Core;

use PHPDaemon\Clients\Mongo\ConnectionFinished;
use PHPDaemon\Core\AppInstance;
use PHPDaemon\Core\Daemon;
use PHPDaemon\Core\Debug;
use PHPDaemon\Core\Timer;

/**
 * Job worker
 */
class JobWorker extends AppInstance {

	public $sessions;
	public $db;
	public $dbname;
	public $components;
	protected $resultCursor;
	protected $runningJobs = [];
	protected $maxRunningJobs = 100;

	public function onReady() {
		$this->db          = \PHPDaemon\Clients\Mongo\Pool::getInstance();
		$this->dbname      = $this->config->dbname->value;
		$this->jobqueue    = $this->db->{$this->dbname . '.jobqueue'};
		$this->jobresults  = $this->db->{$this->dbname . '.jobresults'};
		$this->resultEvent = Timer::add(function ($event) {

			if (!$this->resultCursor) {
				Daemon::log('find start');
				$this->db->{$this->config->dbname->value . '.jobqueue'}->find(function ($cursor) {
					$this->resultCursor = $cursor;
					foreach ($cursor->items as $k => $job) {
						Daemon::log('find: '.json_encode($job));
						if (sizeof($this->runningJobs) >= $this->maxRunningJobs) {
							break;
						}
						$this->jobqueue->update(
							['_id' => $job['_id'], 'status' => 'v'],
							['$set' => ['status' => 'a']],
							0, function($lastError) use ($job) {
								if ($lastError['updatedExisting']) {
									$job['status'] = 'a';
									$this->startJob($job);
								}
							}
						);
						unset($cursor->items[$k]);
					}
					/*if ($cursor->finished) {
						$cursor->destroy();
					}*/
				}, [
					   'tailable' => true,
					   'sort'     => ['$natural' => 1],
					   'where'    => [
						   //'type' => ['$in' => $this->jobs],
						   'status'  => 'v',
						   'shardId' => ['$in' => isset($this->config->shardid->value) ? [null, $this->config->shardid->value] : [null]]
					   ]
				   ]);
				$this->log('inited cursor');
				$event->timeout(1e6);
				return;
			}
			if (!$this->resultCursor->isBusyConn()) {
				try {
					$this->resultCursor->getMore();
				} catch (ConnectionFinished $e) {
					$this->resultCursor = false;
				}
			}
			$event->timeout(0.02e6);
		}, 1);
	}

	public function startJob($job) {
		$jobId = (string)$job['_id'];
		$class = '\\WakePHP\\Jobs\\' . $job['type'];
		if (!class_exists($class) || !is_subclass_of($class, '\\WakePHP\\Jobs\\Generic')) {
			$class = '\\WakePHP\\Jobs\\JobNotFound';
		}
		Daemon::log('startJob('.$class.')');
		$obj = new $class($job, $this);
		$this->runningJobs[$jobId] = $obj;
		$obj->run();
	}

	public function init() {
		Daemon::log(get_class($this) . ' up.');
		ini_set('display_errors', 'On');
	}
	protected function getConfigDefaults() {
		return array(
			'themesdir'     => dirname(__DIR__) . '/themes/',
			'utilsdir'      => dirname(__DIR__) . '/utils/',
			'localedir'     => dirname(__DIR__) . '/locale/',
			'storagedir'    => '/storage/',
			'ormdir'        => dirname(__DIR__) . '/ORM/',
			'dbname'        => 'WakePHP',
			'defaultlocale' => 'en',
			'defaulttheme'  => 'simple',
			'domain'        => 'host.tld',
			'cookiedomain'  => 'host.tld',
			'shardid'		=> null,
		);
	}

}

if (!function_exists('igbinary_serialize')) {
	function igbinary_serialize($m) {
		return serialize($m);
	}
}

if (!function_exists('igbinary_unserialize')) {
	function igbinary_unserialize($m) {
		return unserialize($m);
	}
}
