<?php

abstract class ExternalAuthAgent {

	protected $appInstance;

	public function __construct($appInstance) {

		$this->appInstance = $appInstance;
	}
	
	
}