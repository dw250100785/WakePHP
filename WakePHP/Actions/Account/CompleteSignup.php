<?php
namespace WakePHP\Actions\Account;

use WakePHP\Actions\Generic;
use PHPDaemon\Request\Generic as Request;
use PHPDaemon\Utils\Encoding;

/**
 * Class CompleteSignup
 * @package WakePHP\Actions\Account
 * @dynamic_fields
 */
class CompleteSignup extends Generic {

	public function perform() {
		$this->req->onSessionRead(function () {
			if (!isset($_SESSION['extAuth'])) {
				$this->req->setResult(['success' => false,
				                       'errors'  => ['email' => 'Session expired']
				]);
				return;
			}
			if (($email = Request::getString($_REQUEST['email'])) === '') {
				$this->req->setResult(['success' => false,
				                       'errors'  => ['email' => 'Empty E-Mail']
				]);
				return;
			}
			if (!isset($_SESSION['credentials']['email'])) {
				$_SESSION['credentials']['email'] = $email;
				$this->req->updatedSession        = true;
			}

			//send
			$credentials = $_SESSION['extAuth'];
			$this->appInstance->externalSignupRequests->getRequestByCredentials($credentials, function ($request) use ($email, $credentials) {
				if (!$request || !isset($request['code'])) {
					$code = $this->cmp->getConfirmationCode($email);
					$this->appInstance->externalSignupRequests->save(['email'       => $email,
					                                                  'code'        => $code,
					                                                  'credentials' => $credentials,
					                                                  'add' 		=> Request::getArray($_SESSION['extAuthAdd'])],
						function ($lastError) use ($email, $code) {
							if (isset($lastError['err']) || isset($lastError['$err'])) {
								$this->req->setResult(['success' => false,
								                       'errors'  => ['email' => 'Sorry, internal error.']]);
								return;
							}
							$this->req->appInstance->Sendmail->mailTemplate('mailAccountCompleteSignup', $email, [
								'email'  => $email,
								'code'   => $code,
								'locale' => $this->req->appInstance->getLocaleName(Request::getString($_REQUEST['LC'])),
							]);
							$this->req->setResult(['success' => true, 'status' => 'sent']);
							return;
						});
				}
				else {
					if ('' === ($user_code = Request::getString($_REQUEST['code']))) {
						$this->req->appInstance->Sendmail->mailTemplate('mailAccountCompleteSignup', $email, [
							'email'  => $email,
							'code'   => $request['code'],
							'locale' => $this->req->appInstance->getLocaleName(Request::getString($_REQUEST['LC'])),
						]);
						$this->req->setResult(['success' => true, 'status' => 'sent']);
						return;
					}
					if ($user_code === $request['code']) {
						$account                = $this->appInstance->accounts->getAccountBase($this->req);
						$account['email']       = $email;
						$account['credentials'] = [$credentials];
						$account = $_SESSION['extAuthAdd'] + $account;
						$this->appInstance->accounts->saveAccount($account, function ($lastError) use ($email, $request) {
							if (isset($lastError['err']) || isset($lastError['$err'])) {
								$this->req->setResult(['success' => false,
								                       'errors'  => ['email' => 'Sorry, internal error.']]);
								return;
							}
							$this->appInstance->accounts->getAccountByEmail($email, function ($account) use ($request) {
								if (!$account) {
									$this->req->setResult(['success' => false,
									                       'errors'  => ['email' => 'Sorry, internal error.']]);
									return;
								}
								$this->appInstance->externalSignupRequests->remove(['_id' => new \MongoId($request['_id'])]);
								$this->cmp->loginAs($account);
								$this->req->setResult(['success' => true, 'status' => 'verified']);
								return;
							});
						});
					}
					else {
						$this->req->setResult(['success' => false, 'errors' => ['code' => 'Wrong code']]);
						return;
					}
				}
			});
		});
	}
}
