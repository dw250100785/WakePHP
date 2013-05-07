<?php
namespace WakePHP\Core;

/**
 * Account component
 */
class Components {

	public $req;

	public function __construct($req) {
		$this->req = $req;
	}

	public function __get($name) {
		$class = '\\WakePHP\\Components\\' . $name;
		if (!class_exists($class)) {
			return false;
		}
		return $this->{$name} = new $class($this->req);
	}
}