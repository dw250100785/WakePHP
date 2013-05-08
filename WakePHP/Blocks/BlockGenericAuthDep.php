<?php
namespace WakePHP\Blocks;

class BlockGenericAuthDep extends Block {

	public function init() {
		$this->req->components->Account->onAuth(function ($result) {
			$this->runTemplate();
		});
	}

}
