<?php
namespace WakePHP\Objects\Account;
use PHPDaemon\Utils\Crypt;

use WakePHP\Objects\Generic;

/**
 * Class Account
 * @package WakePHP\Objects
 */
class Account extends Generic {
	
	public function init() {

	}

	protected function fetchObject($cb) {
		$this->orm->accounts->findOne($cb, ['where' => $this->cond,]);
	}
	public function setPassword($value) {
		$this->setProperty('salt', $this->appInstance->config->cryptsalt->value . Crypt::hash(Daemon::uniqid() . "\x00" . $this['email']));
		$this->setProperty('password', Crypt::hash($value, $this['salt'] . $this->appInstance->config->cryptsaltextra->value));
		return $this;
	}

	public function extractCondFrom($obj) {
		if (isset($obj['_id'])) {
			$this->cond = ['_id' => $obj['_id']];
			if (is_string($this->cond['_id'])) {
				$this->cond['_id'] = new \MongoId($this->cond['_id']);
			}
		}
		elseif (isset($obj['email'])) {
			$this->cond = ['email' => $obj['email']];
		}
	}

	/**
	 * @param string $password
	 * @return bool
	 */
	public function checkPassword($password) {
		if ($this['password'] === null) {
			return false;
		}
		return Crypt::compareStrings($this['password'], Crypt::hash($password, $this['salt'] . $this->appInstance->config->cryptsaltextra->value));
	}

	public function setUsername($value) {
		$this->setProperty('unifiedusername', $this->orm->unifyUsername($value));
		$this->setProperty('username', $value);
		return $this;
	}
	public function setEmail($value) {
		$this->setProperty('email', $this->orm->unifyEmail($value));
		return $this;
	}
	public function setAclgroups($value) {
		 $this->setProperty('aclgroups', array_filter(preg_split('~\s*[,;]\s*~s', $account['aclgroups']), 'strlen'));
		 return $this;
	}
	public function setRegdate($value) {
		$this->setProperty('regdate',  \WakePHP\Utils\Strtotime::parse($value));
		return $this;
	}
	public function confirm() {
		if (!isset($this->update['$unset'])) {
			$this->update['$unset'] = [];
		}
		$this->update['$unset']['confirmationcode'] = 1;
		return $this;
	}

	public function addACLgroup($group) {
		if (!is_string($group)) {
			return;
		}
		if (!isset($this->update['$addToSet'])) {
			$this->update['$addToSet'] = [];
		}
		if (!isset($this->update['$addToSet']['aclgroups']['$each'])) {
			$this->update['$addToSet']['aclgroups'] = ['$each' => []];
		}
		$this->update['$addToSet']['aclgroups']['$each'][] = $group;
	}

	public function addCredentials($credentials) {
		if (!isset($this->update['$push'])) {
			$this->update['$push'] = [];
		}
		if (!isset($this->update['$push']['credentials']['$each'])) {
			$this->update['$push']['credentials'] = ['$each' => []];
		}
		$this->update['$push']['credentials']['$each'][] = $credentials;
	}

	protected function removeObject($cb) {
		if (!sizeof($this->cond)) {
			if ($cb !== null) {
				call_user_func($cb, false);
			}
			return;
		}
		$this->orm->accounts->remove($this->cond);
	}

	protected function countObject($cb) {
		if (!sizeof($this->cond)) {
			if ($cb !== null) {
				call_user_func($cb, false);
			}
			return;
		}
		$this->orm->accounts->count($this->cond);
	}

	protected function saveObject($cb) {
		if ($this->new) {
			$this->orm->accounts->upsertOne(['email' => $this->getEmail()], ['$set' => $this->obj], $cb);
		} else {
			if (!sizeof($this->update)) {
				if ($cb !== null) {
					call_user_func($cb, false);
				}
				return;
			}
			$this->orm->accounts->upsertOne($this->cond, $this->update, $cb);
		}
	}

}
