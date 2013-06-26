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
					   'where'    => array('instance' => $this->appInstance->ipcId, 'ts' => array('$gt' => microtime(true)))
				   ));
				Daemon::log('[JobManager] inited cursor - ' . $this->appInstance->ipcId);
			}
			elseif (!$this->resultCursor->session->busy) {
				try {
					$this->resultCursor->getMore();
				} catch (ConnectionFinished $e) {
					$this->resultCursor = false;
				}
			}
			if (sizeof($this->callbacks)) {
				$event->timeout(0.02e6);
			}
			else {
				$event->timeout(5e6);
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
		$jobId = $this->appInstance->jobqueue->push($type, $args, $ts, $add);
		if ($cb !== NULL) {
			$this->callbacks[(string)$jobId] = $cb;
			\PHPDaemon\Core\Timer::setTimeout($this->resultEvent, 0.02e6);
		}
		return $jobId;
	}
}
