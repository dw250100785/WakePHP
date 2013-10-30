<?php
namespace WakePHP\Objects;

/**
 * Class Account
 * @package WakePHP\Objects
 */
class AccountNew extends Generic {
	
	public function init() {

	}

	protected function fetchObject($cb) {
		$this->orm->accounts->findOne($cb, ['where' => $this->cond,]);
	}

	protected function saveObject($cb) {

	}

}
