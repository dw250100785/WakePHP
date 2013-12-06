<?php
namespace WakePHP\Blocks;
use PHPDaemon\Core\Daemon;
use PHPDaemon\Core\Debug;

class BlockACP extends Block {

	public function init() {
		$this->req->components->Account->onAuth(function () {
			if (!$this->req->account['logged'] || !in_array('Superusers', $this->req->account['aclgroups'])) {
				$this->req->redirectToLogin();
				return;
			}
			$this->runTemplate();
		});
	}

}
