<?php
namespace WakePHP\Actions\Account;

use WakePHP\Actions\Generic;

/**
 * Class KeepAlive
 * @package WakePHP\Actions\Account
 * @dynamic_fields
 */
class KeepAlive extends Generic {

	public function perform() {
		$this->req->onSessionRead(function () {
			if (!isset($_SESSION['expires'])) {
				$this->req->setResult(['success' => false]);
				return;
			}
			$this->req->sessionKeepalive(true);
			$this->req->setResult([
				'success' => true,
				'expires' => $_SESSION['expires'],
				'ttl' => $_SESSION['ttl'],
				'now' => time()
			]);
		});
	}
}
