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

	public $appInstance;
	public $resultCursor;
	public $resultEvent;
	public $callbacks = array();

	public function __construct($appInstance) {
		$this->appInstance = $appInstance;
		$this->init();
	}

	public function init() {

		$JobManager        = $this;
		$appInstance       = $this->appInstance;
		$this->resultEvent = Timer::add(function ($event) use ($JobManager, $appInstance) {

			if (!$JobManager->resultCursor) {
				$appInstance->db->{$appInstance->config->dbname->value . '.jobresults'}->find(function ($cursor) use ($JobManager, $appInstance) {
					$JobManager->resultCursor = $cursor;
					if (sizeof($cursor->items)) {
						Daemon::log('items = ' . Debug::dump($cursor->items));
					}
					foreach ($cursor->items as $k => &$item) {
						$jobId = (string)$item['_id'];
						if (isset($JobManager->callbacks[$jobId])) {
							call_user_func($JobManager->callbacks[$jobId], $item);
							unset($JobManager->callbacks[$jobId]);
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
					   'where'    => array('instance' => $appInstance->ipcId, 'ts' => array('$gt' => microtime(true)))
				   ));
				Daemon::log('[JobManager] inited cursor - ' . $appInstance->ipcId);
			}
			elseif (!$JobManager->resultCursor->session->busy) {
				try {
					$JobManager->resultCursor->getMore();
				} catch (ConnectionFinished $e) {
					$JobManager->resultCursor = false;
				}
			}
			if (sizeof($JobManager->callbacks)) {
				$event->timeout(0.02e6);
			}
			else {
				$event->timeout(5e6);
			}
		});
	}

	public function enqueue($cb, $type, $args) {
		$jobId = $this->appInstance->db->{$this->appInstance->config->dbname->value . '.jobqueue'}->insert(array(
				'type'  => $type,
				'args'     => $args,
				'status'   => 'v',
				'ts'       => microtime(true),
				'instance' => $this->appInstance->ipcId,
		));
		if ($cb !== NULL) {
			$this->callbacks[(string)$jobId] = $cb;
			\PHPDaemon\Core\Timer::setTimeout($this->resultEvent, 0.02e6);
		}
	}
}


