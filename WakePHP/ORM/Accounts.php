<?php
namespace WakePHP\ORM;

use PHPDaemon\Core\Daemon;
use WakePHP\Core\Crypt;
use WakePHP\Core\ORM;

/**
 * Accounts
 */
class Accounts extends ORM {

	/** @var \PHPDaemon\Clients\Mongo\Collection */
	protected $accounts;
	/** @var \PHPDaemon\Clients\Mongo\Collection */
	protected $aclgroups;

	public function init() {
		$this->accounts  = $this->appInstance->db->{$this->appInstance->dbname . '.accounts'};
		$this->aclgroups = $this->appInstance->db->{$this->appInstance->dbname . '.aclgroups'};
	}

	/**
	 * @param string $username
	 * @param callable $cb
	 */
	public function getAccountByName($username, $cb) {
		$this->accounts->findOne($cb, array(
			'where' => array('username' => (string)$username),
		));
	}

	/**
	 * @param callable $cb
	 * @param array $cond
	 */
	public function findAccounts($cb, $cond = array()) {
		$this->accounts->find($cb, $cond);
	}

	/**
	 * @param callable $cb
	 * @param array $cond
	 */
	public function countAccounts($cb, $cond = array()) {
		$this->accounts->count($cb, $cond);
	}

	/**
	 * @param array $cond
	 * @param callable $cb
	 */
	public function deleteAccount($cond = array(), $cb = null) {
		if (sizeof($cond)) {
			if (isset($cond['_id']) && is_string($cond['_id'])) {
				$cond['_id'] = new \MongoId($cond['_id']);
			}
			$this->accounts->remove($cond, $cb);
		}
	}

	/**
	 * @param $ip
	 * @param callable $cb
	 */
	public function getRecentSignupsFromIP($ip, $cb) {

		$this->accounts->count($cb, array('where' => array('ip' => (string)$ip, 'regdate' => array('$gt' => time() - 3600))));

	}

	/**
	 * @param string $username
	 * @param callable $cb
	 */
	public function getAccountByUnifiedName($username, $cb) {
		$this->accounts->findOne($cb, array(
			'where' => array('unifiedusername' => $this->unifyUsername($username)),
		));
	}

	/**
	 * @param string $email
	 * @param callable $cb
	 */
	public function getAccountByUnifiedEmail($email, $cb) {
		$this->accounts->findOne($cb, array(
			'where' => array('unifiedemail' => $this->unifyEmail($email)),
		));
	}

	/**
	 * @param string $email
	 * @param callable $cb
	 */
	public function getAccountByEmail($email, $cb) {
		$this->accounts->findOne($cb, array(
			'where' => array('email' => $email),
		));
	}

	/**
	 * @param $id
	 * @param callable $cb
	 */
	public function getAccountById($id, $cb) {
		$this->accounts->findOne($cb, array(
			'where' => array('_id' => $id),
		));
	}

	/**
	 * @param array $find
	 * @param callable $cb
	 */
	public function getAccount($find, $cb) {
		$this->accounts->findOne($cb, array(
			'where' => $find,
		));
	}

	/**
	 * @param $name
	 * @param callable $cb
	 */
	public function getACLgroup($name, $cb) {
		$this->aclgroups->findOne($cb, array(
			'where' => array('name' => $name),
		));
	}

	/**
	 * @param array $account
	 * @param string $password
	 * @return bool
	 */
	public function checkPassword($account, $password) {
		if ($account && !isset($account['password'])) {
			return false;
		}
		return $account['password'] === Crypt::hash($password, $account['salt'] . $this->appInstance->config->cryptsaltextra->value);
	}

	/**
	 * @param string $username
	 * @return string
	 */
	public function unifyUsername($username) {
		static $equals = array(
			'з3z', 'пn', 'оo0', 'еeё',
			'б6b', 'хx', 'уyu', 'ийiu!ия1',
			'мm', 'кk', 'аa', 'ьb',
			'сcs', 'tт', 'йиu', 'i!1',
			'рp', 'tт', 'нh'
		);
		$result = mb_strtolower(preg_replace_callback('~([' . implode('])|([', $equals) . '])~u', function ($m) use ($equals) {
			return '[' . $equals[max(array_keys($m)) - 1] . ']';
		}, $username), 'UTF-8');
		return $result;
	}

	/**
	 * @param string $email
	 * @return string
	 */
	public function unifyEmail($email) {
		static $hosts = array(
			'googlemail.com' => 'gmail.com'
		);
		$email = mb_strtolower($email, 'UTF-8');

		list ($name, $host) = explode('@', $email . '@');
		if (($p = strpos($name, '+')) !== false) {
			$name = substr($name, 0, $p);
		}

		$name = str_replace('.', '', $name);
		$host = rtrim(str_replace('..', '.', $host), '.');
		if (isset($hosts[$host])) {
			$host = $hosts[$host];
		}

		return $name . '@' . $host;
	}

	/**
	 * @param $account
	 * @param callable $cb
	 */
	public function confirmAccount($account, $cb = null) {
		$this->accounts->update($account, array('$unset' => array('confirmationcode' => 1)), 0, $cb);
	}

	/**
	 * @param array $account
	 * @param string $group
	 * @param callable $cb
	 */
	public function addACLgroupToAccount($account, $group, $cb = null) {
		if (isset($account['_id']) && is_string($account['_id'])) {
			$account['_id'] = new \MongoId($account['_id']);
		}
		if (!is_string($group)) {
			return;
		}
		$this->accounts->update($account, array('$addToSet' => array('aclgroups' => $group)), 0, $cb);
	}

	/**
	 * @param array $account
	 * @param array $credentials
	 * @param callable $cb
	 */
	public function addCredentialsToAccount($account, $credentials, $cb = null) {
		if (isset($account['_id'])) {
			$find = ['_id' => is_string($account['_id']) ? new \MongoId($account['_id']) : $account['_id']];
		}
		elseif (isset($account['email'])) {
			$find = ['email' => $account['email']];
		}
		else {
			$find = $account;
		}
		$this->accounts->update($find, ['$push' => ['credentials' => $credentials]], 0, $cb);
	}

	/**
	 * @param $account
	 * @param $update
	 * @param callable $cb
	 */
	public function updateAccount($account, $update, $cb = null) {
		if (isset($account['_id']) && is_string($account['_id'])) {
			$account['_id'] = new \MongoId($account['_id']);
		}
		$this->accounts->update($account, $update, 0, $cb);
	}

	/**
	 * @param $account
	 * @param callable $cb
	 * @param bool $update
	 */
	public function saveAccount($account, $cb = null, $update = false) {
		if (isset($account['password'])) {
			$account['salt']     = $this->appInstance->config->cryptsalt->value . Crypt::hash(Daemon::uniqid() . "\x00" . $account['email']);
			$account['password'] = Crypt::hash($account['password'], $account['salt'] . $this->appInstance->config->cryptsaltextra->value);
		}
		if (isset($account['username'])) {
			$account['unifiedusername'] = $this->unifyUsername($account['username']);
		}
		if (isset($account['regdate']) && is_string($account['regdate'])) {
			$account['regdate'] = \WakePHP\Utils\Strtotime::parse($account['regdate']);
		}
		if (isset($account['aclgroups']) && is_string($account['aclgroups'])) {
			$account['aclgroups'] = array_filter(preg_split('~\s*[,;]\s*~s', $account['aclgroups']), 'strlen');
		}
		if (isset($account['email'])) {
			$account['unifiedemail'] = $this->unifyEmail($account['email']);
		}

		if (isset($account['_id'])) {
			if (is_string($account['_id'])) {
				$account['_id'] = new \MongoId($account['_id']);
			}
			$cond = array('_id' => $account['_id']);
		}
		else {
			$cond = array('email' => $account['email']);
		}
		if ($update) {
			unset($account['_id']);
			$this->accounts->update($cond, array('$set' => $account), 0, $cb);
		}
		else {
			$this->accounts->upsert($cond, $account, false, $cb);
		}
	}

	/**
	 * @param $req
	 * @return array
	 */
	public function getAccountBase($req) {
		return [
			'email'            => '',
			'username'         => '',
			'location'         => '',
			'password'         => '',
			'ukey'			   => Crypt::randomString(16),
			'confirmationcode' => substr(md5($req->attrs->server['REMOTE_ADDR'] . "\x00"
											 . Daemon::uniqid() . "\x00"
											 . $this->appInstance->config->cryptsalt->value . "\x00"
											 . microtime(true) . "\x00"
											 . mt_rand(0, mt_getrandmax()))
				, 0, 6),
			'regdate'          => time(),
			'etime'            => time(),
			'ip'               => $req->attrs->server['REMOTE_ADDR'],
			'subscription'     => 'daily',
			'aclgroups'        => array('Users'),
			'acl'              => array(),
		];
	}

	/**
	 * @param $group
	 */
	public function saveACLgroup($group) {
		$this->aclgroups->upsert(array('name' => $group['name']), array('$set' => $group));
	}

}
