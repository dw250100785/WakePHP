<?php

/**
 * Accounts
 */
class Accounts extends ORM {

	public function init() {
		$this->accounts = $this->appInstance->db->{$this->appInstance->dbname . '.accounts'};
		$this->aclgroups = $this->appInstance->db->{$this->appInstance->dbname . '.aclgroups'};
	}
	public function getAccountByName($username, $cb) {
		$this->accounts->findOne($cb,array(
				'where' =>	array('username' => $username),
		));
	}
	public function getAccountById($id, $cb) {
		$this->accounts->findOne($cb,array(
				'where' =>	array('_id' => $id),
		));
	}
	public function getAccount($find,	$cb) {
		$this->accounts->findOne($cb,array(
				'where' =>	$find,
		));
	}
	public function getACLgroup($name) {
		$this->aclgroups->findOne($cb,array(
				'where' =>	array('name' => $name),
		));
	}
	public function checkPassword($account,$password) {
		if ($account && !isset($account['password'])) {
			return true;
		}
		return crypt($password,$account['password']) === $account['password'];
	}
	public function saveAccount($account) {
		if (isset($account['password'])) {
			$account['password'] = crypt($account['password'],$this->appInstance->config->cryptsalt->value);
		}
		$this->accounts->upsert(array('username' => $account['username']),$account);
	}
	public function saveACLgroup($group) {
		$this->aclgroups->upsert(array('name' => $group['name']),array('$set' => $group));
	}
		
}
