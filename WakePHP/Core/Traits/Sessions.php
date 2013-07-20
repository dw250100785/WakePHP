<?php
namespace WakePHP\Core\Traits;
use PHPDaemon\Core\Daemon;
use PHPDaemon\Core\Debug;

/**
 * Sessions
 *
 * @package Core
 *
 * @author  Zorin Vasily <maintainer@daemon.io>
 */

trait Sessions {
	
	protected function sessionDecode($str) {
		$this->setSessionState($str);
		return $str !== false;
	}
	public function sessionRead($sid, $cb = null) {
		$this->appInstance->sessions->getSessionById($sid, function ($session) use ($cb) {
			call_user_func($cb, $session);
		});
	}

	protected function sessionStartNew($cb = null) {
		$session = $this->appInstance->sessions->startSession(
			['ip' => $this->getIp(), 'useragent' => Request::getString($this->attrs->server['HTTP_USER_AGENT'])],
			function ($lastError) use (&$session, $cb) {
				if (!$session) {
					if ($cb !== null) {
						call_user_func($cb, false);
					}
					return;
				}
				$this->sessionId = (string) $session['id'];
				$this->attrs->session = $session;
				$this->setcookie(
			  		ini_get('session.name')
					, $this->sessionId
					, ini_get('session.cookie_lifetime')
					, ini_get('session.cookie_path')
					, $this->appInstance->config->cookiedomain->value ?: ini_get('session.cookie_domain')
					, ini_get('session.cookie_secure')
					, ini_get('session.cookie_httponly')
				);
				call_user_func($cb, true);
			});
	}

	public function sessionCommit($cb = null) {
		if ($this->updatedSession) {
			$this->appInstance->sessions->saveSession($this->attrs->session, $cb);
		} else {
			if ($cb !== null) {
				call_user_func($cb);
			}
		}
	}

}
