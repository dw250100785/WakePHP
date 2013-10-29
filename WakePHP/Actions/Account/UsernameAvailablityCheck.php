<?php
namespace WakePHP\Actions\Account;

use WakePHP\Actions\Generic;
use PHPDaemon\Request\Generic as Request;

/**
 * Class UsernameAvailablityCheck
 * @package WakePHP\Actions\Account
 * @dynamic_fields
 * @TODO Availablity => Availability
 */
class UsernameAvailablityCheck extends Generic {

	public function perform() {
		$username = Request::getString($_REQUEST['username']);
		if (($r = $this->cmp->checkUsernameFormat($username)) !== true) {
			$this->req->setResult(['success' => true, 'error' => $r]);
			return;
		}
		$this->appInstance->accounts->getAccountByUnifiedName($username, function ($account) {
			if ($account) {
				$this->req->setResult(['success' => true, 'error' => 'Username already taken.']);
			}
			else {
				$this->req->setResult(['success' => true]);
			}
		});
	}
}
