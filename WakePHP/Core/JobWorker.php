<?php
namespace WakePHP\Core;

use PHPDaemon\Clients\Mongo\Collection;
use PHPDaemon\Clients\Mongo\ConnectionFinished;
use PHPDaemon\Core\AppInstance;
use PHPDaemon\Core\Daemon;
use PHPDaemon\Core\Debug;
use PHPDaemon\Core\Timer;
use WakePHP\ORM\Sessions;

/**
 * Job worker
 * @dynamic_fields
 */
class JobWorker extends AppInstance {
	/** @var  Collection */
	public $jobqueue;
	public $jobresults;
	protected $resultEvent;

	/**
	 * @var Sessions[]
	 */
	public $sessions;
	/**
	 * @var
	 */
	public $db;
	/**
	 * @var
	 */
	public $dbname;
	/**
	 * @var
	 */
	public $components;
	/**
	 * @var
	 */
	protected $resultCursor;
	/**
	 * @var array
	 */
	protected $runningJobs = [];
	/**
	 * @var int
	 */
	protected $maxRunningJobs = 100;

	protected $jobs = [];

	public $httpclient;
	/**
	 *
	 */
	public function onReady() {
		$this->db          = \PHPDaemon\Clients\Mongo\Pool::getInstance($this->config->mongoname->value);
		$this->dbname      = $this->config->dbname->value;
		foreach (Daemon::glob($this->config->ormdir->value . '*.php') as $file) {
			$class         = strstr(basename($file), '.', true);
			$prop          = preg_replace_callback('~^[A-Z]+~', function ($m) {return strtolower($m[0]);}, $class);
			$class         = '\\WakePHP\\ORM\\' . $class;
			$this->{$prop} = &$a; // trick ;-)
			unset($a);
			$this->{$prop} = new $class($this);
		}
		$this->httpclient = \PHPDaemon\Clients\HTTP\Pool::getInstance();
		$this->components = new Components($this->fakeRequest());
		$this->resultEvent = Timer::add(function ($event) {
			/** @var Timer $event */
			if (!$this->resultCursor) {
				$types = array_merge($this->jobs, [null]);
				Daemon::log('find start: '.Debug::dump($types));
				$this->db->{$this->config->dbname->value . '.jobqueue'}->find(function ($cursor) {
					if ($cursor->isDead()) {
						$cursor->destroy();
						$this->resultCursor = null;
						return;
					}
					$this->resultCursor = $cursor;
					$this->resultCursor->getMore(1);
					foreach ($cursor->items as $k => $job) {
						Daemon::log('find: ' . json_encode($job));
						if (sizeof($this->runningJobs) >= $this->maxRunningJobs) {
							break;
						}
						$this->jobqueue->update(
							['_id' => $job['_id'], 'status' => 'v'],
							['$set' => ['status' => 'a']],
							0, function ($lastError) use ($job) {
								if ($lastError['updatedExisting']) {
									$job['status'] = 'a';
									$this->startJob($job);
								}
							}
						);
						unset($cursor->items[$k]);
					}
				}, [
					   'tailable' => true,
					   'sort'     => ['$natural' => 1],
					   'where'    => [
						   //'type' => ['$in' => $types],
						   'status'  => 'v',
						   'shardId' => ['$in' => isset($this->config->shardid->value) ? [null, $this->config->shardid->value] : [null]],
						   'serverId' => ['$in' => isset($this->config->serverid->value) ? [null, $this->config->serverid->value] : [null]],
					   ]
				   ]);
				$this->log('inited cursor');
				$event->timeout(1e6);
				return;
			}
			$event->timeout(0.02e6);
		}, 1);
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
		$obj                       = new $class($job, $this);
		$this->runningJobs[$jobId] = $obj;
		$obj->run();
	}

	public function unlinkJob($id) {
		unset($this->runningJobs[(string) $id]);
	}

	/**
	 *
	 */
	public function init() {
		Daemon::log(get_class($this) . ' up.');
		ini_set('display_errors', 'On');
	}

	/**
	 * @return array
	 */

	protected function getConfigDefaults() {
		return array(
			'themesdir'     => 'themes/',
			'utilsdir'      => 'WakePHP/Utils/',
			'localedir'     => 'locale/',
			'storagedir'    => '/storage/',
			'ormdir'        => 'WakePHP/ORM/',
			'dbname'        => 'WakePHP',
			'defaultlocale' => 'en',
			'defaulttheme'  => 'simple',
			'domain'        => 'host.tld',
			'cookiedomain'  => 'host.tld',
			'mongoname' 	=> '',
		);
	}

}
