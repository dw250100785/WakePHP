<?php

/**
 * AccountRecoveryRequestsORM
 */
class AccountRecoveryRequestsORM extends ORM {

	public function init() {
		$this->accountRecoveryRequests = $this->appInstance->db->{$this->appInstance->dbname . '.accountRecoveryRequests'};
	}
	public function getAccountByName($username, $cb) {
		$this->accounts->findOne($cb, array(
				'where' =>	array('username' => $username),
		));
	}
	
	public function checkRecoveryCode($cb, $cond) {
		$this->accounts->find($cb, $cond);
	}

	public function addRecoveryCode($email) {
		
		$this->accountRecoveryRequests->insert(array(
			'email' => $email,
			'ts' => microtime(true),
			'code' => $code = substr(md5(
																			$email . "\x00"
																		. $this->>appInstance->config->cryptsalt->value . "\x00"
																		. microtime(true)."\x00"
																		. mt_rand(0, mt_getrandmax()))
																	, 0, 10)
		));
	}
}
