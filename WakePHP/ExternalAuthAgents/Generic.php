<?php
namespace WakePHP\ExternalAuthAgents;

use PHPDaemon\Core\Daemon;
use WakePHP\Core\Component;
use WakePHP\Core\Request;

abstract class Generic {
	protected $appInstance;
	protected $req;
	protected $cmp;

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
		if (!ctype_alnum($agent) || !class_exists($class) || !(is_subclass_of($class, '\\WakePHP\\ExternalAuthAgents\\Generic'))) {
			return false;
		}
		return new $class($cmp);
	}

	abstract public function auth();

	abstract public function redirect();

	public function checkReferer($domain) {
		return (isset($_SERVER['HTTP_REFERER']) ? $this->req->checkDomainMatch(null, $domain) : true);
	}
}
