<?php
namespace WakePHP\Blocks;

use PHPDaemon\Request\Generic as Request;

class BlockAccountInfo extends Block {

	public function init() {

		$block = $this;
		$this->req->appInstance->accounts->getAccountByName(Request::getString($_GET['username']), function ($account) use ($block) {
			$block->assign('account', $account);
			$block->runTemplate();
		});
	}

}
