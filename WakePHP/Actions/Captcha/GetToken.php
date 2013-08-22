<?php
namespace WakePHP\Actions\Captcha;
use PHPDaemon\Core\ComplexJob;
use PHPDaemon\Core\Daemon;
use PHPDaemon\Core\Debug;
use WakePHP\Core\Request;

/**
 * Class GetToken
 * @package WakePHP\Actions
 * @dynamic_fields
 */
class GetToken extends \WakePHP\Actions\Generic {

	public function perform() {
		$this->appInstance->captcha->newToken(function($token) {
			$this->req->setResult(['success' => $token !== false, 'token' => $token]);
		});	
	}
}
