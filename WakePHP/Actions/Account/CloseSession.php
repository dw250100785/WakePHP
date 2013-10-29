<?php
namespace WakePHP\Actions\Account;

use WakePHP\Actions\Generic;
use PHPDaemon\Request\Generic as Request;

/**
 * Class CloseSession
 * @package WakePHP\Actions\Account
 * @dynamic_fields
 */
class CloseSession extends Generic {

	public function perform() {
		$this->cmp->onAuth(function() {
			if (!$this->req->account['logged']) {
				$this->req->setResult(['success' => false, 'error' => 'Not logged in.']);
				return;
			}
			$this->appInstance->sessions->closeSession(Request::getString($_REQUEST['id']), $this->req->account['_id'], function ($lastError) {
				$this->req->setResult(['success' => $lastError['n'] > 0]);
			});
		});
	}
}
