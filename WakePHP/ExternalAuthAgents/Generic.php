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

	abstract public function Auth();
}
