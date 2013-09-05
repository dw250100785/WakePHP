<?php
namespace WakePHP\Blocks;

class BlockACP extends Block {

	public function init() {
		$this->req->components->Account->onAuth(function () {
			if (!$this->req->account['logged'] || !in_array('Superusers', $this->req->account['aclgroups'])) {
				$this->req->redirectTo(['/' . $this->req->locale . '/account/login', 'backurl' => $this->req->attrs->server['REQUEST_URI']]);
				$this->req->finish();
				return;
			}
			$this->runTemplate();
		});
	}

}
