<?php
namespace WakePHP\Blocks;

use PHPDaemon\Request\Generic as Request;
use WakePHP\Core\Block;

class BlockAccountConfirmation extends Block {

	public function init() {

		$this->req->components->Account->onAuth(function ($result) {
			if (isset($this->req->attrs->request['email'])) {
				$email = Request::getString($this->req->attrs->request['email']);
			}
			else {
				if (!$this->req->account['logged']) {
					$this->req->header('Location: /' . $this->req->locale . '/account/login');
					$this->req->finish();
					return;
				}
				$email = $this->req->account['email'];
			}
			$this->assign('status', 'standby');
			if (!isset($this->req->attrs->request['code'])) {
				$this->runTemplate();
				return;
			}
			$this->req->appInstance->accounts->confirmAccount(array(
																  'email'            => $email,
																  'confirmationcode' => trim($this->req->attrs->request['code'])
															  ), function ($result) use ($this, $email) {
				if ($result['updatedExisting']) {
					$this->success();
				}
				else {
					$this->req->appInstance->accounts->getAccountByEmail($email, function ($account) {

						$this->assign('status', isset($account['confirmationcode']) ? 'incorrectCode' : ($account ? 'alreadyConfirmed' : 'accountNotFound'));
						$this->runTemplate();
					});
				}
			});
		});
	}

	public function success() {
		$this->req->header('Location: /' . $this->req->locale . '/account/profile');
		$this->req->finish();
	}

}
