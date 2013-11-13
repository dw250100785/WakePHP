<?php
namespace WakePHP\Objects\Account;
use PHPDaemon\Utils\Crypt;
use PHPDaemon\Core\Daemon;
use PHPDaemon\Core\Debug;

use WakePHP\Objects\Generic;

/**
 * Class Account
 * @package WakePHP\Objects
 */
class Account extends Generic {
	
	public function init() {
	}

	public static function ormInit($orm) {
		$orm->accounts  = $orm->appInstance->db->{$orm->appInstance->dbname . '.accounts'};
	}

	protected function fetchObject($cb) {
		$this->orm->accounts->findOne($cb, ['where' => $this->cond,]);
	}


	/**
	 * @param $password
	 * @return bool|string
	 */
	public static function checkPasswordFormat($password) {
		if (strlen($password) < 4) {
			return 'The chosen password is too short.';
		}
		return true;
	}

	/**
	 * @param $username
	 * @return bool|string
	 */
	public static function checkUsernameFormat($username) {
		if (preg_match('~^(?![\-_\x20])[A-Za-z\d_\-А-Яа-яёЁ\x20]{2,25}(?<![\-_\x20])$~u', $username) == 0) {
			return 'Incorrect username format.';
		}
		elseif (preg_match('~(.)\1\1\1~', $username) > 0) {
			return 'Username contains 4 identical symbols in a row.';
		}
		return true;
	}


	public function setPassword($value) {
		if (($r = static::checkPasswordFormat($value)) !== true) {
			throw new \Exception($r);
		}
		$this->setProperty('salt', $salt = $this->appInstance->config->cryptsalt->value . Crypt::hash(Daemon::uniqid() . "\x00" . $this['email']));
		$this->setProperty('password', Crypt::hash($value, $salt . $this->appInstance->config->cryptsaltextra->value));
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
		return !isset($this->obj['password']) ? false : Crypt::compareStrings($this->obj['password'], Crypt::hash($password, $this->obj['salt'] . $this->appInstance->config->cryptsaltextra->value));
	}

	public function getPassword() {
		return '*SECRET*';
	}

	public function getSalt() {
		return '*SECRET*';
	}

	public function setUsername($value) {
		if (($r = static::checkUsernameFormat($value)) !== true) {
			throw new \Exception($r);
		}
		$this->set('unifiedusername', $this->orm->unifyUsername($value));
		$this->set('username', $value);
		return $this;
	}
	public function setEmail($value) {
		$this->set('email', $value);
		$this->set('unifiedemail', $this->orm->unifyEmail($value));
		return $this;
	}
	public function setAclgroups($value) {
		$this->set('aclgroups', array_filter(is_string($value) ? preg_split('~\s*[,;]\s*~s', $value) : $value, 'strlen'));
		return $this;
	}
	public function setRegdate($value) {
		$this->set('regdate',  \WakePHP\Utils\Strtotime::parse($value));
		return $this;
	}
	public function confirm() {
		$this->unsetProperty('confirmationcode');
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
	public function setGender($gender) {
		if ($gender !== 'm' && $gender !== 'f') {
			$gender = '';
		}
		$this->set('gender', $gender);
	}

	public function setPublicProperty($k, $v) {
		if (!in_array($k, ['name', 'birthdate', 'gender', 'subscription', 'language', 'autoclose', 'password'])) {
			return;
		}
		$this[$k] = $v;
	}

	public function setLocation($value) {
		$this->set('location', $value);
		$this->req->components->GMAPS->geo($value, function ($geo)  use ($cb) {
			$this['locationCoords'] = isset($geo['Placemark'][0]['Point']['coordinates']) ? $geo['Placemark'][0]['Point']['coordinates'] : null;
			$this->save($cb);
		});	
	}

	protected function saveObject($cb) {
		if ($this->new) {
			if ($this->cond === null) {
				$this->extractCondFrom($this->obj);
			}
			$this->orm->accounts->upsertOne($this->cond, $this->obj, $cb);
		} else {
			if (!sizeof($this->update)) {
				if ($cb !== null) {
					call_user_func($cb, true);
				}
				return;
			}
			Daemon::log(Debug::dump($this->update));
			$this->orm->accounts->upsertOne($this->cond, $this->update, $cb);
			$this->update = [];
		}
	}

}
