<?php
namespace WakePHP\Actions\Captcha;
use PHPDaemon\Core\ComplexJob;
use PHPDaemon\Core\Daemon;
use PHPDaemon\Core\Debug;
use WakePHP\Core\Request;

/**
 * Class GetImage
 * @package WakePHP\Actions
 * @dynamic_fields
 */
class GetImage extends \WakePHP\Actions\Generic {
	
	public function checkReferer() {
		return true;
	}
	
	public function perform() {
		$this->appInstance->captcha->get(Request::getString($_REQUEST['token']), function($token) {
			if (!isset($token['img'])) {
				// @TODO: show mock
			} else {
				$this->req->header('Content-Type: image/png');
				echo $token['img']->bin;
			}
			$this->req->finish();
		});	
	}
}
