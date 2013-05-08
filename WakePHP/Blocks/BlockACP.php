<?php
namespace WakePHP\Blocks;

class BlockACP extends Block {

	public function init() {
		$this->req->components->Account->onAuth(function () use ($this) {
			if (!$this->req->account['logged'] || !in_array('Superusers', $this->req->account['aclgroups'])) {
				$this->req->header('Location: /' . $this->req->locale . '/account/login?backurl=' . urlencode($this->req->attrs->server['REQUEST_URI']));
				$this->req->finish();
			}
			else {
				$this->runTemplate();
			}
		});
	}

}
