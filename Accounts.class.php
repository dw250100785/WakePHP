<?php

/**
 * Accounts
 */
class Accounts {

	public function __construct($appInstance) {
		$this->appInstance = $appInstance;
		$this->accounts = $this->appInstance->db->{$this->appInstance->dbname . '.accounts'};
		$this->aclgroups = $this->appInstance->db->{$this->appInstance->dbname . '.aclgroups'};
	}
	public function getAccountByName($username, $cb) {
		$this->getAccount(array('username' => $username), $cb);
	}
	public function getAccount($find) {
		$this->accounts->findOne($cb,array(
				'where' =>	$find,
		));
	}
	public function getACLgroup($name) {
		$this->aclgroups->findOne($cb,array(
				'where' =>	array('name' => $name),
		));
	}
	public function saveAccount($account) {
		if (isset($account['password'])) {
			$account['password'] = crypt($account['password'],$this->appInstance->config->cryptSalt);
		}
		$this->accounts->upsert(array('username' => $account['username'],array('$set' => $account)));
	}
	public function saveACLgroup($group) {
		$this->aclgroups->upsert(array('name' => $group['username'],array('$set' => $account)));
	}
}
