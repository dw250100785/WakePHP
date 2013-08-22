<?php
namespace WakePHP\Jobs;

use PHPDaemon\Core\Daemon;
use PHPDaemon\Core\Debug;

class GenerateCaptchaImage extends Generic {
	public function run() {
		$captcha = new \WakePHP\Utils\CaptchaDraw;
		$captcha->setText($this->args[1]);
		$captcha->show($file = tempnam(sys_get_temp_dir(), 'php'));
		$this->parent->captcha->uploadImage($this->args[0], file_get_contents($file), function ($lastError) {
			Daemon::log(Debug::dump($lastError));
			$this->sendResult(true);
		});
	}
	public function __destruct() {
		Daemon::log('destructed captcha job');
	}
}
