<?php
class BlockAccountConfirmation extends Block {

	public function init() {
		
		$block = $this;
		$block->req->components->Account->onAuth(function($result) use ($block) {
			if (isset($block->req->attrs->request['email'])) {
				$email = Request::getString($block->req->attrs->request['email']);
			}
			else {
				if (!$block->req->account['logged']) {
					$block->req->header('Location: /'.$block->req->locale.'/account/login');
					$block->req->finish();
					return;
				}
				$email = $block->req->account['email'];
			}
			$block->assign('status', 'standby');
			if (!isset($block->req->attrs->request['code'])) {
				$block->runTemplate();
				return;
			}
			$block->req->appInstance->accounts->confirmAccount(array(
				'email' => $email,
				'confirmationcode' => $block->req->attrs->request['code']
			), function ($result) use ($block, $email) {
				if ($result['updatedExisting']) {
					$block->success();
				}
				else {
					$block->req->appInstance->accounts->getAccountByEmail($email, function ($account) use ($block) {
						
						$block->assign('status', isset($account['confirmationcode']) ? 'incorrectCode' : ($account ? 'alreadyConfirmed' : 'accountNotFound'));
						$block->runTemplate();
						
					});
				}
			});
		});
	}
	public function success() {
		$this->req->header('Location: /'.$this->req->locale.'/welcome');
		$this->req->finish();
	}

}
