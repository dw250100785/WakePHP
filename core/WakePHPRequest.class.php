<?php

/**
 * Request class.
 */
class WakePHPRequest extends HTTPRequest {

	public $locale;
	public $path;
	public $html;
	public $inner = array();
	public $startTime;
	public $req;
	public $jobTotal = 0;
	public $jobDone = 0;
	public $tpl;
	public $components;
	public $dispatched = false;
	public $updatedSession = false;
	
	public function init() {
		$this->header('Content-Type: text/html');
		
		$this->req = $this;
		
		$this->components = new Components($this);
		
		$this->startTime = microtime(true);
		
		$this->tpl = $this->appInstance->getQuickyInstance();
		$this->tpl->assign('req',$this);
	}

	public function onReadyBlock($obj) {
		$this->html = str_replace($obj->tag,$obj->html,$this->html);
		unset($this->inner[$obj->_nid]);
		$this->req->wakeup();
	}

	/**
	 * Called when request iterated.
	 * @return integer Status.
	 */
	public function run() {
		
		if ($this->dispatched) {
			goto waiting;
		}		
		
		init:
		
		$this->dispatch();
		
		waiting:
		
		if (($this->jobDone >= $this->jobTotal) && (sizeof($this->inner) == 0)) {
			goto ready;
		}
		$this->sleep(5);
		
		ready:
		
		unset($this->tpl);
		
		echo $this->html;
	}

	/**
	 * URI parser.
	 * @return void.
	 */
	public function dispatch() {	
		$this->dispatched = true;
		$e = explode('/', ltrim($_SERVER['DOCUMENT_URI'],'/'), 2);
		if (($e[0] === 'component') && isset($e[1])) {
		
			$e = explode('/', ltrim($_SERVER['DOCUMENT_URI'],'/'), 4);
			++$this->jobTotal;
			$this->cmpName = $e[1];
			$this->controller = isset($e[2])?$e[2]:'';
			$this->dataType = isset($e[3])?$e[3]:'json';
			if ($this->components->{$this->cmpName}) {
				$method = $this->controller.'Controller';
				if (method_exists($this->components->{$this->cmpName},$method)) {
					$this->components->{$this->cmpName}->$method();
				}
				else {
					$this->setResult(array('errmsg' => 'Undefined controller.'));
				}
			}
			return;
		}

		if (!isset($e[1])) {
			$this->locale = $this->appInstance->config->defaultlocale->value;
			$this->path = '/'.$e[0];
		}
		else {
			$this->locale = $e[0];
			$this->path = '/'.$e[1];
		}
		
		++$this->jobTotal;
		$this->appInstance->blocks->getPage($this->locale,$this->path,array($this,'loadPage'));
	}
	
	public function setResult($result) {
		if ($this->dataType === 'json') {
			$this->header('Content-Type: text/json');
			$this->html = json_encode($result);
		}
		elseif ($this->dataType === 'xml') {
			$converter = new Array2XML();
			$this->header('Content-Type: text/xml');
			$this->html = $converter->convert($result);
		}
		else {
			$this->html = json_encode(array(
				'errmsg' => 'Unsupported data-type.'
			));
		}
		++$this->jobDone;
		$this->wakeup();
	}
	
	public function addBlock($block) {
		if ((!isset($block['type'])) || (!class_exists($class = 'Block'.$block['type']))) {
			$class = 'Block';
		}
		$block['tag'] = new MongoId();
		$block['nowrap'] = true;
		$this->html .= $block['tag'];
		new $class($block,$this);
	}
	
	public function loadPage($page) {
		
		++$this->jobDone;
		
		if (!$page)	{
			++$this->jobTotal;
			$this->appInstance->blocks->getPage($this->locale,'/404',array($this,'loadErrorPage'));
			return;
		}
		$this->addBlock($page);	
	}
	public function loadErrorPage($page) {
		
		++$this->jobDone;
		
		if (!$page) {
			$this->html = 'Unable to load error-page.';
			$this->wakeup();
			return;
		}
		
		$this->addBlock($page);
	
	}
	public function sessionCommit() {
		if ($this->updatedSession) {
			$this->appInstance->sessions->saveSession($this->attrs->session);
		}
	}
	public function onDestruct() {
	 Daemon::log('destruct');
	}
}

