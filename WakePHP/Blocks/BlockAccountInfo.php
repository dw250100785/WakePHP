<?php
namespace WakePHP\Blocks;

use PHPDaemon\Request\Generic as Request;

class BlockAccountInfo extends Block {

	public function init() {
		$this->req->appInstance->accounts->getAccountByName(Request::getString($_GET['username']), function ($account) {
			$this->assign('account', $account);
			$this->runTemplate();
		});
	}

}
