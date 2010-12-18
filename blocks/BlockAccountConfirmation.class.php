<?php
class BlockAccountConfirmation extends Block {

	public function init() {
		
		$block = $this;
		$block->req->components->Account->onAuth(function($result) use ($block) {
			if (!$block->req->account['logged']) {
				$block->req->header('Location: /'.$block->req->locale.'/account/login');
				$block->req->finish();
				return;
			}
			$block->runTemplate();
		});
	}

}
