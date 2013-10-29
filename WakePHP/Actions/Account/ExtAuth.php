<?php
namespace WakePHP\Actions\Account;

use WakePHP\Actions\Generic;
use PHPDaemon\Request\Generic as Request;
use PHPDaemon\Utils\Crypt;
use PHPDaemon\Core\Daemon;
use PHPDaemon\Clients\HTTP\Pool as HTTPClient;

/**
 * Class ExtAuth
 * @package WakePHP\Actions\Account
 * @dynamic_fields
 */
class ExtAuth extends Generic {

	public function perform() {
		$hash = Request::getString($_REQUEST['x']);
		if (!strlen($hash) || base64_decode($hash, true) === false) {
			$this->req->setResult(['success' => false, 'error' => 'Wrong format of extTokenHash']);
			return;
		}
		$this->appInstance->externalAuthTokens->findByExtTokenHash($hash, function ($result) use ($hash) {
			if ($result) {
				$this->req->setResult(['success' => false, 'error' => 'This token was already used.']);
				return;
			}
			$ip       = $this->req->getIp();
			$intToken = Crypt::hash(Daemon::uniqid() . "\x00" . $ip . "\x00" . Crypt::randomString());
			$this->appInstance->externalAuthTokens->save([
				'extTokenHash' => $hash,
				'intToken'     => $intToken,
				'ip'           => $ip,
				'useragent'    => Request::getString($_SERVER['HTTP_USER_AGENT']),
				'ctime'        => microtime(true),
				'status'       => 'new'
			], function ($lastError) use ($intToken) {
				if (!isset($lastError['n']) || $lastError['n'] === 0) {
					$this->req->setResult(['success' => false, 'errors' => ['code' => 'Sorry, internal error.']]);
					return;
				}
				$type = Request::getString($_REQUEST['type']);
				if ($type === 'email') {
					// send email....
				}
				elseif ($type === 'redirect') {
					$this->req->redirectTo(HTTPClient::buildUrl(['/' . $this->req->locale . '/account/extauth', 'i' => $intToken]), false);
				}
				$this->req->setResult(['success' => true, 'intToken' => $intToken]);
			});
		});
	}
}
