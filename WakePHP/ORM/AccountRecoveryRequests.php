<?php
namespace WakePHP\ORM;

use WakePHP\Core\ORM;

/**
 * AccountRecoveryRequests
 */
class AccountRecoveryRequests extends ORM {

	protected $accountRecoveryRequests;

	public function init() {
		$this->accountRecoveryRequests = $this->appInstance->db->{$this->appInstance->dbname . '.accountRecoveryRequests'};
	}

	/**
	 * @param string $email
	 * @param callable $cb
	 */
	public function getLastCodeByEmail($email, $cb) {
		$this->accountRecoveryRequests->findOne($cb, array(
			'where' => array('email' => (string)$email, 'used' => 0),
			'sort'  => array('ts' => -1),
			'limit' => 1,
		));
	}

	/**
	 * @param callable $cb
	 * @param string $email
	 * @param $code
	 */
	public function getCode($cb, $email, $code) {
		$this->accountRecoveryRequests->findOne($cb, array(
			'where' => array(
				'email' => (string)$email,
				'code'  => (string)$code,
			)));
	}

	/**
	 * @param callable $cb
	 * @param string $email
	 * @param $code
	 */
	public function invalidateCode($cb, $email, $code) {
		$this->accountRecoveryRequests->update(array(
												   'email' => (string)$email,
												   'code'  => (string)$code,
												   'used'  => 0,
											   ), array('$set' => array('used' => 1)), 0, $cb);
	}

	/**
	 * @param string $email
	 * @param $ip
	 * @param $password
	 * @return string
	 */
	public function addRecoveryCode($email, $ip, $password) {

		$this->accountRecoveryRequests->insert(array(
												   'email'    => (string)$email,
												   'ts'       => time(),
												   'used'     => 0,
												   'ip'       => $ip,
												   'password' => $password,
												   'code'     => $code = substr(md5(
																					$email . "\x00"
																					. $this->appInstance->config->cryptsalt->value . "\x00"
																					. microtime(true) . "\x00"
																					. mt_rand(0, mt_getrandmax()))
													   , 0, 10)
											   ));
		return $code;

	}
}
