<?php
namespace WakePHP\Actions\Account;

use WakePHP\Actions\Generic;

/**
 * Class Logout
 * @package WakePHP\Actions\Account
 * @dynamic_fields
 */
class Logout extends Generic {

	public function perform() {
		if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
			$this->req->setResult(['success' => false, 'err' => 'POST_METHOD_REQUIRED']);
			return;
		}
		$this->req->onSessionRead(function ($sessionEvent) {
			$this->cmp->loginAs(false);
			$this->req->setResult(['success' => true]);
		});
	}
}
