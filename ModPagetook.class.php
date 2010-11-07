<?php
class ModPagetook extends Module {

	public function execute() {
		$this->html = sprintf($this->html, round(microtime(true) - $this->req->startTime, 6));
		$this->ready();
	}

}
