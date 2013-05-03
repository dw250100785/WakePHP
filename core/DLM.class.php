<?php

/**
 * DLM class.
 */
class DLM {

	public $appInstance;
	public $resultCursor;
	public $resultEvent;
	public $callbacks = array();
	public function __construct($appInstance) {
		$this->appInstance = $appInstance;
		$this->init();
	}
	public function init() {

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


