<?php
namespace WakePHP\Core;

use PHPDaemon\Request\IRequestUpstream;

class TestCase extends \PHPUnit_Framework_TestCase implements IRequestUpstream {
	protected $appInstance;

	/**
	 * @param mixed $appInstance
	 */
	public function setAppInstance($appInstance) {
		$this->appInstance = $appInstance;
	}

	/**
	 * @return Request
	 */
	public function getRequestMock() {
		return new Request($this->appInstance, $this);
	}

	/**
	 * @return mixed
	 */
	public function getAppInstance() {
		return $this->appInstance;
	}

	public function requestOut($req, $s) {
		// TODO: Implement requestOut() method.
	}

	/**
	 * Handles the output from downstream requests.
	 * @return boolean Succcess.
	 */
	public function endRequest($req, $appStatus, $protoStatus) {
		// TODO: Implement endRequest() method.
	}

	/**
	 * Frees this request
	 * @return void
	 */
	public function freeRequest($req) {
		// TODO: Implement freeRequest() method.
	}

	/**
	 * Send Bad request
	 * @return void
	 */
	public function badRequest($req) {
		// TODO: Implement badRequest() method.
	}
}
