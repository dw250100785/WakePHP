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
	
	public function saveAccount($account, $cb = null, $update = false) {
		if (isset($account['password'])) {
			$account['password'] = crypt($account['password'], $this->appInstance->config->cryptsalt->value);
		}
		if (isset($account['username'])) {
			$account['unifiedusername'] = $this->unifyUsername($account['username']);
		}
		if ($update) {
<<<<<<< HEAD
			$this->accounts->update(array('email' => $account['email']), array('$set' => $account), 0, $cb);
=======
			$this->accounts->update(array('email' => $account['email']), $account, 0, $cb);
>>>>>>> 44ebee1d0dadfcafc831afdab604c4fa7708716a
		} else {
			$this->accounts->upsert(array('email' => $account['email']), $account, false, $cb);
		}
	}
	
	public function saveACLgroup($group) {
		$this->aclgroups->upsert(array('name' => $group['name']),array('$set' => $group));
	}
		
}
