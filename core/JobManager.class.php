<?php

/**
 * JobManager class.
 */
class	JobManager {

	public $appInstance;
	public $resultCursor;
	public $resultEvent;
	public $callbacks = array();
	public function __construct($appInstance) {
		$this->appInstance = $appInstance;
		$this->init();
	}
	public function init() {
	
		$JobManager = $this;
		$appInstance = $this->appInstance;
		$this->resultEvent = Daemon_TimedEvent::add(function($event) use ($JobManager, $appInstance) {
			
			if (!$JobManager->resultCursor) {
				Daemon::log($appInstance->config->dbname->value.'.jobresults');
				$appInstance->db->{$appInstance->config->dbname->value.'.jobresults'}->find(function($cursor) use ($JobManager, $appInstance) {
					$JobManager->resultCursor = $cursor;
					if (sizeof($cursor->items)) {Daemon::log('items = '.Debug::dump($cursor->items));}
					foreach ($cursor->items as $k => &$item) {
						$jobId = (string) $item['_id'];
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
					'sort' => array('$natural' => 1),
					'fields' => 'status,result',
					'where' => array('instance' => $appInstance->ipcId, 'ts' => array('$gt' => microtime(true)))
				));
				Daemon::log('[JobManager] inited cursor - '.$appInstance->ipcId);
			}
			elseif (!$JobManager->resultCursor->session->busy) {
				try {
					$JobManager->resultCursor->getMore();
				}
				catch (MongoClientSessionFinished $e) {
					$JobManager->resultCursor = false;
				}
			}
			if (sizeof($JobManager->callbacks)) {
				$event->timeout(pow(10,6) * 0.02);
			}
			else {
				$event->timeout(pow(10,6) * 5);
			}
		});
	}
	public function enqueue($cb, $jobtype, $args) {
		$jobId = $this->appInstance->db->{$this->appInstance->config->dbname->value.'.jobqueue'}->insert(array(
			'jobtype' => $jobtype,
			'args' => $args,
			'status' => 'vacant',
			'ts' => microtime(true),
			'instance' => $this->appInstance->ipcId,
		));
		if ($cb !== NULL) {
			$this->callbacks[(string) $jobId] = $cb;
			Daemon_TimedEvent::setTimeout($this->resultEvent);
		}
	}
}


