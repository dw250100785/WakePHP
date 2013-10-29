<?php
namespace WakePHP\Actions\Account;

use WakePHP\Actions\Generic;
use PHPDaemon\Request\Generic as Request;

/**
 * Class Recovery
 * @package WakePHP\Actions\Account
 * @dynamic_fields
 */
class Recovery extends Generic {

	public function perform() {
		if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
			$this->req->setResult(['success' => false, 'err' => 'POST_METHOD_REQUIRED']);
			return;
		}
		$this->req->onSessionStart(function () {
			if (!isset($_REQUEST['email'])) {
				$this->req->setResult(['success' => false, 'errors' => ['email' => 'Empty E-Mail.']]);
				return;
			}
			$email = Request::getString($_REQUEST['email']);
			$code  = trim(Request::getString($_REQUEST['code']));
			if ($code !== '') {

				$this->appInstance->accountRecoveryRequests->invalidateCode(function ($lastError) use ($email, $code) {
					if ($lastError['n'] > 0) {

						$this->appInstance->accountRecoveryRequests->getCode(function ($result) {
							if (!$result) {
								$this->req->setResult(array('success' => false, 'errors' => array('code' => 'Error happened.')));
								return;
							}

							$this->appInstance->accounts->saveAccount(array(
								'email'    => $result['email'],
								'password' => $result['password'],
							), function ($lastError) use ($result) {
								if ($lastError['updatedExisting']) {
									$this->req->setResult(array('success' => true, 'status' => 'recovered'));

									$this->appInstance->accounts->confirmAccount(array(
										'email' => $result['email'],
									));

								}
								else {
									$this->req->setResult(array('success' => false, 'errors' => array('code' => 'Error happened.')));
								}
							}, true);

						}, $email, $code);

					}
					else {
						$this->req->setResult(array('success' => false, 'errors' => array('code' => 'Incorrect code.')));
					}
				}, $email, $code);
			}
			else {
				$this->appInstance->accounts->getAccountByUnifiedEmail($email, function ($account) use ($email) {
					if (!$account) {
						$this->req->setResult(array('success' => false, 'errors' => array('email' => 'Account not found.')));
						return;
					}
					$this->appInstance->accountRecoveryRequests->getLastCodeByEmail($email, function ($result) use ($email) {

						if ($result['ts'] + 900 > time()) {
							$this->req->setResult(array('success' => false, 'errors' => array('email' => 'Too often. Wait a bit before next try.')));
						}
						else {
							$password = substr(md5($email . "\x00" . $result['code'] . "\x00" . $this->appInstance->config->cryptsalt->value . "\x00" . mt_rand(0, mt_getrandmax())), mt_rand(0, 26), 6);

							$code = $this->appInstance->accountRecoveryRequests->addRecoveryCode($email, Request::getString($_SERVER['REMOTE_ADDR']), $password);

							$this->appInstance->Sendmail->mailTemplate('mailAccountAccessRecovery', $email, array(
								'email'    => $email,
								'password' => $password,
								'code'     => $code,
								'locale'   => $this->req->appInstance->getLocaleName(Request::getString($_REQUEST['LC'])),
							));
							$this->req->setResult(array('success' => true, 'status' => 'sent'));
						}

					});
				});
			}
		});
	}
}
