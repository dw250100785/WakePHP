<?php
namespace WakePHP\Actions\Account;

use WakePHP\Actions\Generic;
use PHPDaemon\Core\ComplexJob;
use PHPDaemon\Core\Daemon;
use PHPDaemon\Core\Debug;
use WakePHP\Core\Request;

/**
 * Class ChangePhone
 * @package WakePHP\Actions\Account
 * @dynamic_fields
 */
class ChangePhone extends Generic {

	public function perform() {
		if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
			//$this->req->setResult(['success' => false, 'err' => 'POST_METHOD_REQUIRED']);
			//return;

		}
		$this->cmp->onAuth(function ($result) {
			if (!$this->req->account['logged']) {
				$this->req->setResult(['success' => false, 'goLoginPage' => true]);
				return;
			}
			try {
				$this->appInstance->sms->newMessage()
					->setPhone(Request::getString($_REQUEST['phone']))
					->genId(function ($msg) {
						$msg
						->setMTAN('#%s Account binding request code: %s. Please ignore this message if unexpected.')
						->send(function($msg, $success) {
							$this->req->setResult($success ? [
								'success' => true,
								'idText' => $msg['idText'],
							] : [
								'success' => false,
								'error' => 'SMS gateway error '
							]);
						});
					});
			} catch (Exception $e) {
				$this->req->setResult(['success' => false, 'error' => $e->getMessage()]);
			}
		});
	}
}
