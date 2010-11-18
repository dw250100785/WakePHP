<?php

/**
 * CAPTCHA component
 */
class CmpCAPCHA extends AsyncServer {

	public $req;
	public $appInstance;
	public function __construct($req) {
		$this->req = $req;
		$this->appInstance = $req->appInstance;
		$this->init();
	}
	public function init() {
	}
	public function __get($event) {
		$this->{$event} = new DeferredEvent($this->{$event.'Event'}());
		$this->{$event}->component = $this;
		return $this->{$event};
	}
	public function cleanup() {
		foreach ($this as $key => $property) {
			if ($property instanceof DeferredEvent) {
				$property->cleanup();
			}
			unset($this->{$key});
		}
	}
	public function __call($event,$args) {
		return call_user_func_array($this->{$event},$args);
	}
	
	public function validate($cb) {
		
		
	}
	
}

