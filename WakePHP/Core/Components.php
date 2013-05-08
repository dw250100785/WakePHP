<?php
namespace WakePHP\Core;
use \PHPDaemon\Core\Daemon;

/**
 * Account component
 */
class Components {

	public $req;

	public function __construct($req) {
		$this->req = $req;
	}

	public function __get($name) {
		$class = '\\WakePHP\\Components\\Cmp' . $name;
		if (!class_exists($class)) {
			Daemon::log(get_class($this) . ': undefined class: ' . $class);
			return false;
		}
		return $this->{$name} = new $class($this->req);
	}
}
