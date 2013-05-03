<?php
namespace WakePHP\core;

/**
 * Account component
 */
class Components {

	public $req;

	public function __construct($req) {
		$this->req = $req;
	}

	public function __get($name) {
		$class = '\\WakePHP\\components\\' . $name;
		if (!class_exists($class)) {
			return false;
		}
		return $this->{$name} = new $class($this->req);
	}
}
