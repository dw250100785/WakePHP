<?php
namespace WakePHP\Core;

use PHPDaemon\Core\Daemon;

/**
 * Sendmail
 */
class Sendmail {
	use \PHPDaemon\Traits\ClassWatchdog;

	/**
	 * @var WakePHP
	 */
	public $appInstance;

	/**
	 * @param WakePHP $appInstance
	 */
	public function __construct($appInstance) {
		$this->appInstance = $appInstance;
	}

	/**
	 * @param string $block
	 * @param string $email
	 * @param $args
	 */
	public function mailTemplate($block, $email, $args) {
		$args['domain'] = $this->appInstance->config->domain->value;
		$this->appInstance->renderBlock($block, $args, function ($result) use ($email) {
			$result = str_replace("\r", '', $result);
			$e      = explode("\\\n\\\n", $result, 2);
			$e[0]   = str_replace("\n", "\r\n", $e[0]);
			$e[0]   = preg_replace('~^\\\r\n~ms', '', $e[0]);

			$subject = preg_match('~^Subject: (.*)$~mi', $e[0], $m) ? $m[1] : '';
			$this->mail($email, $subject, $e[1], $e[0]);
		});
	}

	public function mail() {

		$this->appInstance->JobManager->enqueue(null, 'Mail', func_get_args());

	}
}
