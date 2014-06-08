<?php
namespace WakePHP\Exceptions;
use PHPDaemon\Core\ComplexJob;
use PHPDaemon\Core\Daemon;
use PHPDaemon\Core\Debug;
use PHPDaemon\Core\ClassFinder;

/**
 * Class Generic
 * @package WakePHP\Exceptions
 * @dynamic_fields
 */
class Generic extends \Exception {
	protected $silent = false;
	protected $critical = false;
	public function toArray() {
		return [
			'type' => ClassFinder::getClassBasename($this),
			'code' => $this->getCode(),
			'msg' => $this->getMessage(),
			'critical' => $this->critical,
		];
	}
	public function isSilent() {
		return $this->silent;
	}
	public function isCritical() {
		return $this->critical;
	}
}
