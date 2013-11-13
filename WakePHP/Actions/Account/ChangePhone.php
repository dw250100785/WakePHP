<?php
namespace WakePHP\Actions\Account;

use WakePHP\Actions\Generic;
use PHPDaemon\Core\ComplexJob;
use PHPDaemon\Core\Daemon;
use PHPDaemon\Core\Debug;
use PHPDaemon\Request\Generic as Request;

/**
 * Class ChangePhone
 * @package WakePHP\Actions\Account
 * @dynamic_fields
 */
class ChangePhone extends Generic {

	public function perform() {
		if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
			$this->req->setResult(['success' => false, 'err' => 'POST_METHOD_REQUIRED']);
			return;

		}
		$this->cmp->onAuth(function ($result) {
			if (!$this->req->account['logged']) {
				$this->req->setResult(['success' => false, 'goLoginPage' => true]);
				return;
			}
		});
	}
}
