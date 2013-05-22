<?php
namespace WakePHP\Jobs;
class Mail extends Generic {
	  public function run() {
		$this->setResult(call_user_func_array('mail', $this->args));
	  }
}
