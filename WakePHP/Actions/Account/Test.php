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
		$obj = $this->appInstance->accounts->getAccount($this->req->account['_id']);
		Daemon::log(Debug::dump($obj));
		$this->req->setResult();
	}
}
