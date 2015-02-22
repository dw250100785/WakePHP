<?php
namespace WakePHP\Actions\Account;

use WakePHP\Actions\Generic;
use PHPDaemon\Core\Daemon;
use PHPDaemon\Core\Debug;
use PHPDaemon\Request\Generic as Request;

/**
 * Class Authentication
 * @package WakePHP\Actions\Account
 * @dynamic_fields
 */
class Authentication extends Generic {

	public function perform() {
		if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
			//$this->req->setResult(['success' => false, 'err' => 'POST_METHOD_REQUIRED']);
			//return;
		}
		$this->req->onSessionStart(function ($sessionEvent) {
			$username = Request::getString($_REQUEST['username']);
			if ($username === '') {
				$this->req->setResult([
					'success' => false,
					'errors'  => [
						'username' => 'Unrecognized username.'
					]
				]);
				return;
			}
			$this->appInstance->accounts->getAccount([
					'$or' => [
						['username' => $username],
						['unifiedemail' => $this->appInstance->accounts->unifyEmail($username)],
					]
				], function ($account) {
					if (!$account->exists()) {
						$this->req->setResult([
							'success' => false,
							'errors'  => [
								'username' => 'Unrecognized username.',
							]
						]);
						return;
					}
					if (!$account->checkPassword(Request::getString($_REQUEST['password']))) {
						$this->req->setResult([
							'success' => false,
							'errors'  => [
								'password' => 'Invalid password.',
							]
						]);
						return;
					}
					$this->cmp->loginAs($account, function() use ($account) {
						$r = ['success' => true];
						if (isset($account['confirmationcode'])) {
							$r['needConfirm'] = true;
						}
						$this->req->setResult($r);
					});
				});
		});
	}
}
