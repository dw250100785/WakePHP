<?php
namespace WakePHP\Blocks;

use WakePHP\core\Block;

class BlockPagetook extends Block {

	public $nowrap = true;

	public function execute() {
		$this->html = round(microtime(true) - $this->req->startTime, 6);
		$this->ready();
	}

}