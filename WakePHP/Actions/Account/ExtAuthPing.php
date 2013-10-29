<?php
namespace WakePHP\Actions\Account;

use WakePHP\Actions\Generic;
use PHPDaemon\Request\Generic as Request;

/**
 * Class ExtAuthPing
 * @package WakePHP\Actions\Account
 * @dynamic_fields
 */
class ExtAuthPing extends Generic {

	public function perform() {
		$extToken = Request::getString($_REQUEST['p']);
		if (!strlen($extToken)) {
			$this->req->setResult(['success' => false, 'error' => 'Wrong format of extTokenHash']);
			return;
		}
		$this->appInstance->externalAuthTokens->findByExtToken($extToken, function ($result) {
			if (!$result) {
				$this->req->setResult(['success' => false, 'error' => 'Token not found.']);
				return;
			}
			if ($result['status'] === 'new') {
				$this->req->setResult(['success' => true, 'result' => 'wait']);
				return;
			}
			if ($result['status'] === 'failed') {
				$this->req->setResult(['success' => true, 'result' => 'failed']);
				return;
			}
			if (microtime(true) - $result['ctime'] > 60 * 15) {
				$this->req->setResult(['success' => true, 'result' => 'expired']);
				return;
			}
			$this->appInstance->externalAuthTokens->save([
				'extTokenHash' => $result['extTokenHash'],
				'status'       => 'used',
			], function ($lastError) use ($result) {
				if (!isset($lastError['n']) || $lastError['n'] === 0) {
					$this->req->setResult(['success' => true, 'result' => 'failed']);
					return;
				}
				$this->req->onSessionStart(function ($sessionEvent) use ($result) {
					$this->appInstance->accounts->getAccountById($result['uid'], function ($account) {
						$this->cmp->loginAs($account);
						$this->req->setResult(['success' => true]);
					});
				});
			});
		});
	}
}
