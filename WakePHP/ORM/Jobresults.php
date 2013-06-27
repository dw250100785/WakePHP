<?php
namespace WakePHP\ORM;

use WakePHP\Core\ORM;

/**
 * Jobresults
 */
class Jobresults extends ORM {

	protected $jobresults;

	public function init() {
		$this->jobresults = $this->appInstance->db->{$this->appInstance->dbname . '.jobresults'};
	}
	public function __call($method, $args) {
		return call_user_func_array([$this->jobresults, $method], $args);
	}
}
