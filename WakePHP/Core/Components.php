<?php
namespace WakePHP\Core;

use PHPDaemon\Core\Daemon;

/**
 * Account component
 */
class Components {

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
}
