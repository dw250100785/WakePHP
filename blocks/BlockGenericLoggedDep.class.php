<?php
class BlockGenericLoggedDep extends Block {

	public function init() {
		
		$block = $this;
		$this->req->components->Account->onAuth(function($result) use ($block) {
			if (!$block->req->account['logged']) {
				$block->req->header('Location: /'.$block->req->locale.'/account/login?backurl='.urlencode($block->req->attrs->server['REQUEST_URI']));
				$block->req->finish();
			} else {
				$block->runTemplate();
			}
		});
	}

}
