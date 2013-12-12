<?php
namespace WakePHP\Exceptions;
use PHPDaemon\Core\ComplexJob;
use PHPDaemon\Core\Daemon;
use PHPDaemon\Core\Debug;
use WakePHP\Core\Request;

/**
 * Class WrongField
 * @package WakePHP\Actions
 * @dynamic_fields
 */
class WrongField extends Generic {
	public $field;
	public $silent = true;
	public function field($f) {
		$this->field = $f;
		return $this;
	}
	public function toArray() {
		return parent::toArray() + ['field' => $this->field];
	}
}
