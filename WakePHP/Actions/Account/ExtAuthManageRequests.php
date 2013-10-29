<?php
namespace WakePHP\Actions\Account;

use WakePHP\Actions\Generic;
use PHPDaemon\Request\Generic as Request;

/**
 * Class ExtAuthManageRequests
 * @package WakePHP\Actions\Account
 * @dynamic_fields
 */
class ExtAuthManageRequests extends Generic {

	public function perform() {
		$this->cmp->onAuth(function () {
			if (!$this->req->account['logged']) {
				$this->req->setResult([]);
				return;
			}
			$intToken = Request::getString($_REQUEST['request_token']);
			if ($intToken === '') {
				$this->req->setResult([]);
				return;
			}
			$answer = Request::getString($_REQUEST['answer']);
			if (!in_array($answer, ['yes', 'no', 'not_sure'])) {
				$this->req->setResult([]);
				return;
			}
			$this->appInstance->externalAuthTokens->findByIntToken($intToken, function ($authToken) use ($answer) {
				if (!$authToken) {
					$this->req->setResult([]);
					return;
				}
				if ($answer === 'yes') {
					$authToken['status'] = 'accepted';
				}
				elseif ($answer === 'no') {
					$authToken['status'] = 'rejected';
				}
				elseif ($answer === 'not_sure') {
					$authToken['status'] = 'delayed';
				}
				$this->appInstance->externalAuthTokens->save($authToken, function ($result) {
					if (!empty($result['err'])) {
						$this->req->status(500);
						$this->req->setResult(['success' => false]);
					}
					else {
						$this->req->setResult(['success' => true]);
					}
					return;
				});
			});
		});
	}
}
