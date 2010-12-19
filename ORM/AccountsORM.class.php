<?php

/**
 * AccountsORM
 */
class AccountsORM extends ORM {

	public function init() {
		$this->accounts = $this->appInstance->db->{$this->appInstance->dbname . '.accounts'};
		$this->aclgroups = $this->appInstance->db->{$this->appInstance->dbname . '.aclgroups'};
	}
	public function getAccountByName($username, $cb) {
		$this->accounts->findOne($cb, array(
				'where' =>	array('username' => $username),
		));
	}
	
	public function findAccounts($cb, $cond = array()) {
		$this->accounts->find($cb, $cond);
	}
	
	public function countAccounts($cb, $cond = array()) {
		$this->accounts->count($cb, $cond);
	}	
	
	public function getRecentSignupsFromIP($ip, $cb) {
	
		$this->accounts->count($cb, array('ip' => $ip, 'regdate' => array('$gt' => time() - 3600)));
		
	}
	
	public function getAccountByUnifiedName($username, $cb) {
		$this->accounts->findOne($cb, array(
				'where' =>	array('unifiedusername' => $this->unifyUsername($username)),
		));
	}
	
	public function getAccountByEmail($email, $cb) {
		$this->accounts->findOne($cb, array(
				'where' =>	array('email' => $email),
		));
	}
	
	public function getAccountById($id, $cb) {
		$this->accounts->findOne($cb, array(
				'where' =>	array('_id' => $id),
		));
	}
	
	public function getAccount($find,	$cb) {
		$this->accounts->findOne($cb, array(
				'where' =>	$find,
		));
	}
	
	public function getACLgroup($name) {
		$this->aclgroups->findOne($cb, array(
				'where' =>	array('name' => $name),
		));
	}
	
	public function checkPassword($account,$password) {
		if ($account && !isset($account['password'])) {
			return true;
		}
		return crypt($password,$account['password']) === $account['password'];
	}
	
	public function unifyUsername($username) {
		static $equals = array(
			'з3z',	'пn',			'оo0',	'еeё',
			'б6b',	'хx',			'уyu',	'ийiu!ия1',
			'мm',		'кk',			'аa',		'ьb',
			'сcs',	'tт',			'йиu',	'i!1',
			'рp',		'tт',			'нh'
		);
		$result = mb_strtolower(preg_replace_callback('~(['.implode('])|([',$equals).'])~u', function ($m) use ($equals) {
			return '['.$equals[max(array_keys($m))-1].']';
		}, $username),'UTF-8');
		return $result;
	}
	
	public function confirmAccount($account, $cb = null) {
		$this->accounts->update($account, array('$unset' => array('confirmationcode' => 1)), 0, $cb);
	}
	
	
	public function saveAccount($account, $cb = null, $update = false) {
		if (isset($account['password'])) {
			$account['password'] = crypt($account['password'], $this->appInstance->config->cryptsalt->value);
		}
		if (isset($account['username'])) {
			$account['unifiedusername'] = $this->unifyUsername($account['username']);
		}
		if (isset($account['_id'])) {
			if (is_string($account['_id'])) {
				$account['_id'] = new MongoId($account['_id']);
			}
			$cond = array('_id' => $account['_id']);
		}
		else {
			$cond = array('email' => $account['email']);
		}
		if ($update) {
			unset($account['_id']);
			$this->accounts->update($cond, array('$set' => $account), 0, $cb);
		} else {
			$this->accounts->upsert($cond, $account, false, $cb);
		}
	}
	
	public function saveACLgroup($group) {
		$this->aclgroups->upsert(array('name' => $group['name']),array('$set' => $group));
	}
		
}
