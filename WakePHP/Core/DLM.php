<?php
namespace WakePHP\Core;

use PHPDaemon\Core\Timer;

/**
 * DLM class.
 */
class DLM {
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

	}

	/**
	 * @param $cb
	 * @param $jobtype
	 * @param $args
	 */
	public function enqueue($cb, $jobtype, $args) {
		$jobId = $this->appInstance->db->{$this->appInstance->config->dbname->value . '.jobqueue'}->insert(
			[
				'jobtype'  => $jobtype,
				'args'     => $args,
				'status'   => 'vacant',
				'ts'       => microtime(true),
				'instance' => $this->appInstance->ipcId,
			]);
		if ($cb !== NULL) {
			$this->callbacks[(string)$jobId] = $cb;
			Timer::setTimeout($this->resultEvent);
		}
	}
}
