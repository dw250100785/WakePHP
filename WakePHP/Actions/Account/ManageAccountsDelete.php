<?php
namespace WakePHP\Actions\Account;

use WakePHP\Actions\Generic;
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
			$this->req->appInstance->accounts->deleteAccount(['_id' => Request::getString($_REQUEST['id'])], function ($lastError) {

				if ($lastError['n'] > 0) {
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
