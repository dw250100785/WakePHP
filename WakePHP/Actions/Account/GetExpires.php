<?php
namespace WakePHP\Actions\Account;

use WakePHP\Actions\Generic;

/**
 * Class GetExpires
 * @package WakePHP\Actions\Account
 * @dynamic_fields
 */
class GetExpires extends Generic {

	public function perform() {
		$this->req->noKeepalive = true;
		$this->req->onSessionRead(function () {
			if (!isset($_SESSION['expires'])) {
				$this->req->setResult(['success' => false]);
				return;
			}
			$this->req->setResult([
				'success' => true,
				'expires' => $_SESSION['expires'],
				'ttl' => $_SESSION['ttl'],
				'now' => time()
			]);
		});
	}
}
