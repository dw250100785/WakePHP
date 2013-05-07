<?php
namespace WakePHP\Blocks;

use WakePHP\core\Block;

class BlockGenericLoggedDep extends Block {

	public function init() {
		$this->req->components->Account->onAuth(function () use ($this) {
			if (!$this->req->account['logged']) {
				$this->req->header('Location: /' . $this->req->locale . '/account/login?backurl=' . urlencode($this->req->attrs->server['REQUEST_URI']));
				$this->req->finish();
			}
			else {
				$this->runTemplate();
			}
		});
	}

}
