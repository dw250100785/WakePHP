<?php

/* Класс описывающий запрос.

 */
class xErequest extends HTTPRequest {

	public $lang;
	public $path;
	public $html;
	public $jobTotal = 0;
	public $jobDone = 0;
	public $jobCounter = 0;
	public $placeholders = array();
	public $startTime;
	
	public function init() {
	
		$this->startTime = microtime(true);
		$this->appInstance->statistics->registerRequest($this); // Отправляем текущий запрос на обработку модулю стастики
		
		$this->dispatch(); // Вычленяем из урла язык и название странички.
		
		$this->html = $this->templateFetch('index.html');
		
		$this->appInstance->placeholders->parse($this);
		
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
		
		if ($this->jobTotal > $this->jobDone) {
			$this->sleep(5);
		}
		
		echo $this->html;
		
	}
	//public function 
	
	/* Вычленяем из урла язык и название странички. */
	public function dispatch() {
		
		$e = explode('/',$_SERVER['DOCUMENT_URI'],3);
		if (in_array($e[1],$this->appInstance->languages)) {
			$this->lang = $e[1];
		}
		else {
			$this->lang = $this->appInstance->languages[0];
		}
		$this->path = isset($e[2])?rtrim($e[2],'/'):'';
	}
	
}
