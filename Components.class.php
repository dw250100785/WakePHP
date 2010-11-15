<?php

/**
 * Account component
 */
class Components {

	public $req;
	public function __construct($req) {
		$this->req = $req;
	}
	public function __get($name) {
		$class = 'Cmp'.$name;
		return $this->{$name} = new $class($this->req);
	}
}
