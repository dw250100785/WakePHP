<?php
namespace WakePHP\ORM;

use WakePHP\ORM\Generic;

/**
 * Jobresults
 */
class Jobresults extends Generic {

	protected $jobresults;

	public function init() {
		$this->jobresults = $this->appInstance->db->{$this->appInstance->dbname . '.jobresults'};
	}
	public function __call($method, $args) {
		return call_user_func_array([$this->jobresults, $method], $args);
	}
}
