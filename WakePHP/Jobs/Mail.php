<?php
namespace WakePHP\Jobs;
use PHPDaemon\Core\Daemon;
use PHPDaemon\Core\Debug;
class Mail extends Generic {
	  public function run() {
	  	Daemon::log(Debug::dump($this->args));
		$this->sendResult(call_user_func_array('mail', $this->args));
	  }
}
