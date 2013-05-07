<?php
namespace WakePHP\Components\Muchat;

use PHPDaemon\Request;

class MsgQueueRequest extends Request {
	public function run() {
		foreach ($this->appInstance->tags as $tag) {
			$tag->touch();
		}
		$this->sleep(0.1);
	}
}
