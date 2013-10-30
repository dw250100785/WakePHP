<?php
namespace WakePHP\Actions\Account;

use WakePHP\Actions\Generic;
use PHPDaemon\Core\Daemon;
use PHPDaemon\Core\Debug;

/**
 * Class Test
 * @package WakePHP\Actions\Account
 * @dynamic_fields
 */
class Test extends Generic {

	public function perform() {
		$this->appInstance->accounts->getAccountNew($this->req->account['_id'], function($account) {
			$this->req->setResult($account['ip']);
		});
		
	}
}
