<?php
namespace WakePHP\Actions\Account;

use WakePHP\Actions\Generic;

/**
 * Class CloseThisSession
 * @package WakePHP\Actions\Account
 * @dynamic_fields
 */
class CloseThisSession extends Generic {

	public function perform() {
		$this->cmp->onAuth(function() {
			if (!$this->req->account['logged']) {
				$this->req->setResult(['success' => false, 'error' => 'Not logged in.']);
				return;
			}
			$this->appInstance->sessions->closeSessionByObjectId($_SESSION['_id'], $this->req->account['_id'], function ($lastError) {
				$this->req->setResult(['success' => $lastError['n'] > 0]);
			});
		});
	}
}
