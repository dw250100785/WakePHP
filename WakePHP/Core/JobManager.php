<?php
namespace WakePHP\Core;

use PHPDaemon\Clients\Mongo\ConnectionFinished;
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

	protected $lastTs;

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
		$this->resultEvent = Timer::add(function ($event) {
//Daemon::log('timer called');
			if (!$this->resultCursor) {
				$this->appInstance->db->{$this->appInstance->config->dbname->value . '.jobresults'}->find(function ($cursor)  {
					$this->resultCursor = $cursor;
					if (sizeof($cursor->items)) {
						Daemon::log('items = ' . Debug::dump($cursor->items));
					}
					foreach ($cursor->items as $k => &$item) {
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
					   'where'    => array(
					   		'instance' => $this->appInstance->ipcId,
					   		'ts' => array('$gt' => $this->lastTs)
					   	)
				   ));
				$this->lastTs = null;
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
		if ($this->lastTs === null) {
			$this->lastTs = $ts;
		}
		$jobId = $this->appInstance->jobqueue->push($type, $args, $ts, $add, function ($lastError) use ($cb, &$jobId) {
			if ($cb !== NULL) {
				$this->callbacks[(string)$jobId] = $cb;
				Daemon::log('setTimeout!');
				\PHPDaemon\Core\Timer::setTimeout($this->resultEvent, 0.02e6);
			}
		});
		return $jobId;
	}
}
