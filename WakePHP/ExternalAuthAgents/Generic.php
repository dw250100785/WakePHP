<?php
namespace WakePHP\ExternalAuthAgents;

abstract class Generic {

	protected $appInstance;

	public function __construct($appInstance) {

		$this->appInstance = $appInstance;
	}

}