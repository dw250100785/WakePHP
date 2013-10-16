<?php
namespace WakePHP\Core\Traits;
use PHPDaemon\Core\Daemon;
use PHPDaemon\Core\Debug;
use WakePHP\Core\Request;

/**
 * Sessions
 *
 * @package Core
 *
 * @author  Zorin Vasily <maintainer@daemon.io>
 */

trait Sessions {

	protected $defaultSessionTTL = 1200;
	
	protected function sessionDecode($str) {
		$this->setSessionState($str);
		return $str !== false;
	}
	public function sessionRead($sid, $cb = null) {
		$this->appInstance->sessions->getSessionById($sid, function ($session) use ($cb) {
			if ($session) {
				$this->sessionId = (string) $session['id'];
			}
			call_user_func($cb, $session);
		});
	}
	public function sessionKeepalive() {
		if ($this->attrs->session) {
			$this->updatedSession = true;
			$this->attrs->session['expires'] = time() + (isset($this->attrs->session['ttl']) ? $this->attrs->session['ttl'] : $this->defaultSessionTTL);
		}
	}

	protected function sessionStartNew($cb = null) {
		$this->getBrowser(function() use ($cb) {
			$session = $this->appInstance->sessions->startSession(
			[
				'ip' => $this->getIp(),
				'atime' => time(),
				'expires' => time() + $this->defaultSessionTTL,
				'browser' => [
					'agent' => $this->browser['_id'],
					'os' => $this->browser['platform'],
					'name' => $this->browser['name'],
					'majorver' => $this->browser['majorver'],
					'comment' => $this->browser['comment'],
					'ismobiledevice' => $this->browser['ismobiledevice'],
				],
				'location' => 'UNK',
			],
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
