<?php
namespace WakePHP\Core;

use PHPDaemon\Core\Daemon;

/**
 * Sendmail
 */
class Sendmail {

	public $appInstance;

	public function __construct($appInstance) {
		$this->appInstance = $appInstance;
	}

	public function mailTemplate($block, $email, $args) {
		$appInstance    = $this->appInstance;
		$args['domain'] = $appInstance->config->domain->value;
		$appInstance->renderBlock($block, $args, function ($result) use ($email, $appInstance) {
			$result = str_replace("\r", '', $result);
			$e      = explode("\\\n\\\n", $result, 2);
			$e[0]   = str_replace("\n", "\r\n", $e[0]);
			$e[0]   = preg_replace('~^\\\r\n~ms', '', $e[0]);

			$subject = preg_match('~^Subject: (.*)$~mi', $e[0], $m) ? $m[1] : '';
			$appInstance->Sendmail->mail($email, $subject, $e[1], $e[0]);
		});
	}

	public function mail() {

		$this->appInstance->JobManager->enqueue(null, 'mail', func_get_args());

	}
}
