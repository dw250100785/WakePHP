<?php
namespace WakePHP\core;

/**
 * Job worker
 */
class JobWorker extends \AppInstance {

	public $sessions;
	public $db;
	public $dbname;
	public $LockClient;
	public $components;

	public function onReady() {
		$this->db          = \MongoClientAsync::getInstance();
		$this->dbname      = $this->config->dbname->value;
		$this->jobqueue    = $this->db->{$this->dbname . '.jobqueue'};
		$this->jobresults  = $this->db->{$this->dbname . '.jobqueue'};
		$this->resultEvent = \Timer::add(function ($event) {

			if (!$this->resultCursor) {
				$this->db->{$this->config->dbname->value . '.jobqueue'}->find(function ($cursor) use ($JobManager, $appInstance) {
					$JobManager->resultCursor = $cursor;
					if (sizeof($cursor->items)) {
						Daemon::log('items = ' . \Debug::dump($cursor->items));
					}
					foreach ($cursor->items as $k => &$item) {
						$jobId = (string)$item['_id'];
						Daemon::log(\Debug::dump($item));
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
						   'status'  => 'vacant',
						   'shardId' => ['$in' => [null, $this->config->shardId->value]]
					   ]
				   ]);
				$this->log('inited cursor');
			}
			elseif (!$JobManager->resultCursor->session->busy) {
				try {
					$JobManager->resultCursor->getMore();
				} catch (\MongoClientSessionFinished $e) {
					$JobManager->resultCursor = false;
				}
			}
			if (sizeof($JobManager->callbacks)) {
				$event->timeout(pow(10, 6) * 0.02);
			}
			else {
				$event->timeout(pow(10, 6) * 5);
			}
		});
	}

	public function sendResult($job, $result) {
		$status = $result !== false ? 'succeed' : 'failed';
		$this->jobqueue->update(
			['_id' => $job['_id']],
			['$set' => ['status' => $status]]
		);
		$this->jobresults->insert([
									  '_id'      => $job['_id'],
									  'ts'       => microtime(true),
									  'instance' => $job['instance'],
									  'status'   => $status,
									  'result'   => $result
								  ]);

		$this->jobresults->insert([
									  'jobId'    => $job['_id'],
									  'ts'       => microtime(true),
									  'instance' => $job['instance'],
									  'status'   => '',
									  'result'   => $result
								  ]);
	}

	public function init() {
		Daemon::log(get_class($this) . ' up.');
		ini_set('display_errors', 'On');
	}

//	protected function getConfigDefaults() {
//		return array(
//			'ormdir' =>	dirname(__DIR__).'/ORM/',
//			'dbname' => 'WakePHP',
//		);
//	}
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
