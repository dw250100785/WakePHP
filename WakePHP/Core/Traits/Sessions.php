<?php
namespace WakePHP\Core\Traits;
use PHPDaemon\Core\Daemon;
use PHPDaemon\Core\Debug;
use PHPDaemon\Core\ComplexJob;
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
	public $noKeepalive = false;
	
	public function redirectToLogin() {
		if ($this->pjax) {
			$this->header('X-PJAX-Version: refresh');
		} else {
			$this->redirectTo(['/' . $this->locale . '/account/login', 'backurl' => $this->attrs->server['REQUEST_URI']]);
		}
	}
	protected function sessionDecode($str) {
		$this->setSessionState($str);
		return $str !== false;
	}
	public function sessionRead($sid, $cb = null) {
		$this->appInstance->sessions->getSessionById($sid, function ($session) use ($cb) {
			if ($session) {
				$this->sessionId = (string) $session['id'];
				if (!$this->noKeepalive) {
					$this->sessionKeepalive();
				}
			}
			call_user_func($cb, $session);
		});
	}
	public function sessionKeepalive($force = false) {
		$s = &$this->attrs->session;
		if (!$s) {
			return;
		}
		if ($force || (time() - $s['expires'] < $s['ttl'] * 0.8)) {
			 $this->updatedSession = true;
		}
	}

	protected function sessionStartNew($cb = null) {
		$job = new ComplexJob(function() use (&$job, $cb) {
			$session = $this->appInstance->sessions->startSession([
				'ip' => $this->getIp(),
				'atime' => time(),
				'expires' => time() + $this->defaultSessionTTL,
				'ttl' => $this->defaultSessionTTL,
				'browser' => [
					'agent' => $this->browser['_id'],
					'os' => $this->browser['platform'],
					'name' => $this->browser['name'],
					'majorver' => $this->browser['majorver'],
					'comment' => $this->browser['comment'],
					'ismobiledevice' => $this->browser['ismobiledevice'],
				],
				'location' => $job->getResult('geoip'),
			], function ($lastError) use (&$session, $cb) {
				if (!$session) {
					if ($cb !== null) {
						call_user_func($cb, false);
					}
					return;
				}
				$this->sessionId = (string) $session['id'];
				$this->attrs->session = $session;
				try {
					$this->setcookie(
			  			ini_get('session.name')
						, $this->sessionId
						, ini_get('session.cookie_lifetime')
						, ini_get('session.cookie_path')
						, $this->appInstance->config->cookiedomain->value ?: ini_get('session.cookie_domain')
						, ini_get('session.cookie_secure')
						, ini_get('session.cookie_httponly')
					);
				} finally {
					call_user_func($cb, true);
				}
			});
		});
		$job('browser', function($jobname, $job) {
			$this->getBrowser(function() use ($jobname, $job) {
				$job->setResult($jobname);
			});
		});
		$job('geoip', function($jobname, $job) {
			$this->appInstance->geoIP->query($this->getIp(true), function($loc) use ($jobname, $job) {
				$job->setResult($jobname, $loc);
			});
		});
		$job();
	}

	public function sessionCommit($cb = null) {
		if ($this->updatedSession) {
			if (!$this->noKeepalive) {
				$this->attrs->session['atime'] = time();
				$this->attrs->session['expires'] = time() + $this->attrs->session['ttl'];
			}
			$this->appInstance->sessions->saveSession($this->attrs->session, $cb);
		} else {
			if ($cb !== null) {
				call_user_func($cb);
			}
		}
	}

}
