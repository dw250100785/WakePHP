<?php
namespace WakePHP\Blocks;

use PHPDaemon\Request;
use WakePHP\Core\Block;

class BlockAccountInfo extends Block {

	public function init() {

		$block = $this;
		$this->req->appInstance->accounts->getAccountByName(Request::getString($this->req->attrs->get['username']), function ($account) use ($block) {
			$block->assign('account', $account);
			$block->runTemplate();
		});
	}

}
