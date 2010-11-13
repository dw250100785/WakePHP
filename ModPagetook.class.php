<?php

class ModPagetook extends Block {

	public $nowrap = true;
	public function execute() {
		$this->html = round(microtime(true) - $this->req->startTime, 6);
			Daemon::log($this->html);
		$this->ready();
	}

}
