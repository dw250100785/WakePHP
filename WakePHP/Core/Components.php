<?php
namespace WakePHP\Core;

use PHPDaemon\Core\Daemon;

/**
 * Account component
 * @dynamic_fields
 */
class Components {
	use \PHPDaemon\Traits\ClassWatchdog;

	/**
	 * @var Request
	 */
	public $req;

	/**
	 * @param Request $req
	 */
	public function __construct($req) {
		$this->req = $req;
	}

	/**
	 * @param string $name
	 * @return bool
	 */
	public function __get($name) {
		$class = '\\WakePHP\\Components\\' . $name;
		if (!class_exists($class)) {
			Daemon::log(get_class($this) . ': undefined class: ' . $class);
			return false;
		}
		return $this->{$name} = new $class($this->req);
	}

	public function cleanup() {
		foreach ($this as $k => $c) {
			if ($k === 'req') {
				continue;
			}
			$c->cleanup();
		}
	}
}
