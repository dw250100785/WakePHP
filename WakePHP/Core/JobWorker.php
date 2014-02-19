<?php
namespace WakePHP\Core;

use PHPDaemon\Clients\Mongo\Collection;
use PHPDaemon\Clients\Mongo\ConnectionFinished;
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

	protected $jobs;
	
	protected $jobtypes = [];

	public $httpclient;
	/**
	 *
	 */

	public $mycb;
	public function onReady() {
		$this->db          = \PHPDaemon\Clients\Mongo\Pool::getInstance($this->config->mongoname->value);
		$this->dbname      = $this->config->dbname->value;
		foreach (Daemon::glob($this->config->ormdir->value . '*.php') as $file) {
			$class         = strstr(basename($file), '.', true);
			if ($class === 'Generic') {
				continue;
			}
			$prop          = preg_replace_callback('~^[A-Z]+~', function ($m) {return strtolower($m[0]);}, $class);
			$class         = '\\WakePHP\\ORM\\' . $class;
			$this->{$prop} = &$a; // trick ;-)
			unset($a);
			$this->{$prop} = new $class($this);
		}
		$this->httpclient = \PHPDaemon\Clients\HTTP\Pool::getInstance();
		$this->components = new Components($this->fakeRequest());
		$this->jobqueue = $this->db->{$this->config->dbname->value . '.jobqueue'};
		$this->jobs = $this->db->{$this->config->dbname->value . '.jobs'};
		$this->resultEvent = Timer::add(function ($event) {
			/** @var Timer $event */
			$event->timeout(5e6);
			if (sizeof($this->runningJobs) >= $this->maxRunningJobs) {
				return;
			}
			if (!$this->resultCursor) {
				$types = array_merge($this->jobtypes, [null]);
				Daemon::log('find start: '.Debug::dump($types));
				$this->jobqueue->find(function ($cursor) use ($event) {
					if ($cursor->isDead()) {
						Daemon::log('dead!');
						$cursor->destroy();
						$this->resultCursor = null;
						return;
					}
					$event->timeout(1e6 * 0.05);
					$this->resultCursor = $cursor;
					foreach ($cursor->items as $k => $job) {
						Daemon::log('find: ' . json_encode($job));
						++$this->runningJobs;
						$this->jobs->findAndModify([
							'query' => [
								'status' => 'v',
								//'type' => ['$in' => $types],
								'shardId' => ['$in' => isset($this->config->shardid->value) ? [null, $this->config->shardid->value] : [null]],
						   		'serverId' => ['$in' => isset($this->config->serverid->value) ? [null, $this->config->serverid->value] : [null]],
							],
							'update' => ['$set' => ['status' => 'a']],
							'sort' => ['priority' => -1],
							'new' => true,
							], function ($ret) {
								$job = isset($ret['value']) ? $ret['value']: false;
								if (!$job) {
									return;
								}
								$job['status'] = 'a';
								$this->startJob($job);
							}
						);
						unset($cursor->items[$k]);
					}
				}, [
					   'tailable' => true,
					   'sort'     => ['$natural' => 1],
					   'where'    => [
					   		'ts' => ['$gt' => microtime(true)]
					   ]
				   ]);
				$event->timeout(1e6);
				return;
			} else {
				$this->resultCursor->getMore(1);
			}
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
