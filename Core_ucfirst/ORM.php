<?php
namespace WakePHP\Core;

/**
 * ORM class.
 */
class ORM {

	public $appInstance;

	public function __construct($appInstance) {
		$this->appInstance = $appInstance;
		$this->init();
	}

	public function init() {
	}
}

