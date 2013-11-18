<?php
namespace WakePHP\ExternalAuthAgents;

use PHPDaemon\Core\ClassFinder;
use PHPDaemon\Core\Daemon;
use WakePHP\Core\Component;
use WakePHP\Core\Request;

abstract class Generic {
	use \PHPDaemon\Traits\ClassWatchdog;
	use \PHPDaemon\Traits\StaticObjectWatchdog;
	protected $appInstance;
	protected $req;
	protected $cmp;
	protected $backUrl;

	public function __construct(Component $cmp) {
		$this->cmp         = $cmp;
		$this->req         = $cmp->req;
		$this->appInstance = $cmp->appInstance;
	}

	/**
	 * @param $agent
	 * @param Component $cmp
	 * @return \WakePHP\ExternalAuthAgents\Generic|bool
	 */
	public static function getAgent($agent, Component $cmp) {
		$class = '\\WakePHP\\ExternalAuthAgents\\' . $agent;
		if (!class_exists($class) || !(is_subclass_of($class, '\\WakePHP\\ExternalAuthAgents\\Generic'))) {
			return false;
		}
		return new $class($cmp);
	}

	abstract public function auth();

	abstract public function redirect();

	public function checkReferer($domain) {
		return (isset($_SERVER['HTTP_REFERER']) ? $this->req->checkDomainMatch(null, $domain) : true);
	}

	public function getCallbackURL() {
		$params = ['agent' => ClassFinder::getClassBasename($this)];
		if (isset($_GET['external_token'])) {
			$params['external_token'] = Request::getString($_GET['external_token']);
		}
		if (isset($this->backUrl)) {
			$params['backurl'] = $this->backUrl;
		}
		return $this->req->getBaseUrl() . '/component/Account/ExternalAuthRedirect/json?' . http_build_query($params);
	}

	/**
	 * @return string
	 */
	public function getBackUrl($empty = false) {
		if (strlen($this->backUrl)) {
			return $this->backUrl;	
		}
		if ($empty) {
			return '';
		}
		return $this->req->getBaseUrl() . '/' . $this->req->locale . '/';
	}

	/**
	 * @param string $backUrl
	 */
	public function setBackUrl($backUrl) {
		$this->backUrl = $backUrl;
	}

	public function finalRedirect() {
		$this->req->redirectTo($this->getBackUrl());
		$this->req = null;
	}
}
