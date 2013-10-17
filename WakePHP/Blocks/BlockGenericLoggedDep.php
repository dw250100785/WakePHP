<?php
namespace WakePHP\Blocks;

class BlockGenericLoggedDep extends Block {

	public function init() {
		$this->req->components->Account->onAuth(function ()	{
			if (!$this->req->account['logged'])	{
				$this->req->redirectToLogin();
				return;
			}
			$this->runTemplate();
		});
	}

}
