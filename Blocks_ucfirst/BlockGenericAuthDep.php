<?php
namespace WakePHP\Blocks;

use WakePHP\core\Block;

class BlockGenericAuthDep extends Block {

	public function init() {
		$this->req->components->Account->onAuth(function ($result) {
			$this->runTemplate();
		});
	}

}
