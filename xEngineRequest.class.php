<?php

/**
 * Request class.
 */
class xEngineRequest extends HTTPRequest {

	public $lang;
	public $path;
	public $html;
	public $inner = array();
	public $startTime;
	public $req;
	public $jobTotal = 0;
	public $jobDone = 0;
	
	public function init() {
		$this->req = $this;
		
		$this->startTime = microtime(true);
		
		$this->dispatch();
		
		$this->html = $this->templateFetch('index.html');
		$this->appInstance->placeholders->parse($this);
		Daemon::log('init '.$this->html);
	}

	public function onReadyBlock($obj) {
		$this->html = str_replace($obj->tag,$obj->html,$this->html);
	}

	public function templateFetch($path) {
		$appInstance->quicky->lang = $this->lang;
		return $this->appInstance->quicky->fetch($path);
	}
	
	/**
	 * Called when request iterated.
	 * @return integer Status.
	 */
	public function run() {
		if (($this->jobTotal > $this->jobDone) || (sizeof($this->inner) > 0)) {
			$this->sleep(5);
		}

		echo $this->html;
	}

	/**
	 * URI parser.
	 * @return void.
	 */
	public function dispatch() {	
		$e = explode('/', $_SERVER['DOCUMENT_URI'], 3);

		if (in_array($e[1], $this->appInstance->languages)) {
			$this->lang = $e[1];
		} else {
			$this->lang = $this->appInstance->languages[0];
		}

		$this->path = isset($e[2]) ? rtrim($e[2], '/') : '';
	}
	
}

