<?php
namespace WakePHP\Components;

use PHPDaemon\Clients\HTTP\Pool as HTTPClient;
use PHPDaemon\Clients\Mongo\Cursor;
use PHPDaemon\Core\ComplexJob;
use PHPDaemon\Core\Daemon;
use PHPDaemon\Core\Debug;
use WakePHP\Core\Component;
use WakePHP\Core\DeferredEventCmp;
use WakePHP\Core\Request;

/**
 * Captcha component
 * @method onSessionStart(callable $cb)
 * @method onAuth(callable $cb)
 */
class Captcha extends Component {
	public function checkReferer() {
		return true;
	}
	public static function checkJob($invalidate = true) {
		return function ($jobname, $job) use ($invalidate) {
			$token = Request::getString($job->req->attrs->request['captcha_token']);
			if ($token === '')  {
				$job->setResult($jobname, ['captcha' => 'need']);
				return;
			}
			$job->req->appInstance->captcha->check(
				$token,
				Request::getString($job->req->attrs->request['captcha_text']),
				$invalidate,
			 	function ($result) use ($jobname, $job) {
			 		$errors = [];
			 		if ($result !== 'ok') {
			 			$errors['captcha'] = $result;
			 		}
					$job->setResult($jobname, $errors);
			});
		};
	}


}
