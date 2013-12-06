<?php
namespace WakePHP\Actions\Account;

use WakePHP\Actions\Generic;
use PHPDaemon\Core\Daemon;
use PHPDaemon\Core\Debug;
use PHPDaemon\Request\Generic as Request;

/**
 * Class ManageAccountsDelete
 * @package WakePHP\Actions\Account
 * @dynamic_fields
 */
class ManageAccountsDelete extends Generic {

	public function perform() {
		$this->cmp->onAuth(function ($result) {
			if (!in_array('Superusers', $this->req->account['aclgroups'], true)) {
				$this->req->setResult(['success' => false, 'goLoginPage' => true]);
				return;
			}
			$this->req->appInstance->accounts->getAccount()->condSetId(Request::getString($_REQUEST['id']))
			->delete()->debug()->remove(function ($o) {
				if ($o->lastError(true)) {
					$this->req->setResult(['success' => true]);
				}
				else {
					$this->req->setResult([
						'success' => false,
						'error'   => 'Account not found.'
					]);
				}
			});

		});
	}
}
