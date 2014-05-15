<?php
namespace WakePHP\Core;

use PHPDaemon\Clients\Mongo\ConnectionFinished;
use PHPDaemon\Clients\Mongo\MongoId;
use PHPDaemon\Core\Daemon;
use PHPDaemon\Core\Debug;
use PHPDaemon\Core\Timer;

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
	 * @var
	 */
	public $resultCursor;
	/**
	 * @var
	 */
	public $resultEvent;
	/**
	 * @var array
	 */
	public $callbacks = array();

	protected $lastId;

	protected $jobresults;

	/**
	 * @param WakePHP $appInstance
	 */
	public function __construct($appInstance) {
		$this->appInstance = $appInstance;
		$this->init();
		$this->appInstance->redis->subscribe('jobFinished:'.$this->appInstance->ipcId, function($redis) {
			Daemon::log(Debug::dump($redis->result));
		});
		$this->jobresults = $this->appInstance->db->{$this->appInstance->config->dbname->value . '.jobresults'};
	}

	/**
	 *
	 */
	public function init() {
		$this->resultEvent = Timer::add(function ($event) {
//Daemon::log('timer called');
			if (!$this->resultCursor) {
				$where = [
					'instance' => $this->appInstance->ipcId,
				];
				if ($this->lastId !== null) {
					$where['_id'] = ['$gt' => $this->lastId];
				}
				$this->jobresults->find(function ($cursor)  {
					$this->resultCursor = $cursor;
					if (sizeof($cursor->items)) {
						Daemon::log('items = ' . Debug::dump($cursor->items));
					}
					foreach ($cursor->items as $k => &$item) {
						$item['_id'] = MongoId::import($item['_id']);
						$jobId = (string)$item['_id'];
						if (isset($this->callbacks[$jobId])) {
							call_user_func($this->callbacks[$jobId], $item);
							unset($this->callbacks[$jobId]);
						}
						unset($cursor->items[$k]);
					}
					/*if ($cursor->finished) {
						$cursor->destroy();
					}*/
				}, array(
					   'tailable' => true,
					   'sort'     => array('$natural' => 1),
					   'fields'   => 'status,result',
					   'where'    => $where,
				   ));
				$this->lastId = null;
				Daemon::log('[JobManager] inited cursor - ' . $this->appInstance->ipcId);
			}
			elseif (!$this->resultCursor->isBusyConn()) {
				try {
					$this->resultCursor->getMore();
				} catch (ConnectionFinished $e) {
					$this->resultCursor = false;
				}
			}
			if (sizeof($this->callbacks)) {
				$event->timeout(0.02e6);
			} else {
				if ($this->resultCursor) {
					$this->resultCursor->destroy();
					$this->resultCursor = null;
				}
			}
		});
	}

	/**
	 * @param $cb
	 * @param $type
	 * @param $args
	 */
	public function enqueue($cb, $type, $args, $add = []) {
		$ts = microtime(true);
		return $this->appInstance->jobqueue->push($type, $args, $ts, $add, function ($job) use ($cb) {
			if ($cb !== NULL) {
				$this->callbacks[$job->getId()] = $cb;
				Daemon::log('setTimeout!');
				\PHPDaemon\Core\Timer::setTimeout($this->resultEvent, 0.02e6);
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
