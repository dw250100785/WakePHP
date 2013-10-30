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
		$emptyState = $this->appInstance->accounts->getAccountNew($this->req->account['_id'], function($obj) {
			Daemon::log(Debug::dump(['loadedObject', $obj]));
		});
		Daemon::log(Debug::dump(['emptyState', $emptyState]));
		$this->req->setResult();
	}
}
