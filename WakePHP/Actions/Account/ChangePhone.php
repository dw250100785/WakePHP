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
				if (isset($_REQUEST['idText'])) {
					$this->appInstance->sms->getMessage()
					->setPhone(Request::getString($_REQUEST['phone']))
					->setIdText(Request::getString($_REQUEST['idText']))
					->attr('user', $this->req->account['_id'])
					->checkCode(Request::getString($_REQUEST['code']), function($msg, $success, $tries = null) {
						if ($success) {
							$this->req->setResult(['success' => true]);
						} else {
							$this->req->setResult(['success' => false, 'tries' => $tries]);
						}
					});
					return;
				}
				$this->appInstance->sms->newMessage()
					->setPhone(Request::getString($_REQUEST['phone']))
					->genId(function ($msg) {
						$msg
						->setMTAN('#%s Account binding request code: %s. Please ignore this message if unexpected.')
						->attr('user', $this->req->account['_id'])
						->antiflood(function($msg, $flood) {
							if ($flood) {
								$this->req->setResult([
									'success' => false,
									'errcode' => 'TOO_FAST',
									'error' => 'Too fast',
								]);
								return;
							}
							$msg->send(function($msg, $success) {
								$this->req->setResult($success ? [
									'success' => true,
									'idText' => $msg['idText'],
								] : [
									'success' => false,
									'errcode' => 'SMSGATE_ERR',
									'error' => 'SMS gateway error',
								]);
							});
						});
					});
			} catch (Exception $e) {
				$this->req->setResult(['success' => false, 'error' => $e->getMessage()]);
			}
		});
	}
}
