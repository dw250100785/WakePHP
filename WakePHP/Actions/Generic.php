<?php
namespace WakePHP\Actions;
use PHPDaemon\Core\CallbackWrapper;

/**
 * Class Generic
 * @package WakePHP\Actions
 * @dynamic_fields
 */
abstract class Generic {
	use \PHPDaemon\Traits\ClassWatchdog;

	protected $appInstance;
	protected $callback;

	public function __construct($params = []) {
		foreach ($params as $k => $v) {
			$this->{$k} = $v;
		}
	}

	public function setAppInstance($appInstance) {
		$this->appInstance = $appInstance;

	}

	public function setCallback($cb) {
		$this->callback = CallbackWrapper::wrap($cb);
	}

	abstract public function perform();

	
}
