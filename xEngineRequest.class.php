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
	public $tpl;
	
	public function init() {
		$this->req = $this;
		
		$this->startTime = microtime(true);
		
		$this->tpl = new Quicky;
		$this->tpl->template_dir = $this->appInstance->config->templatedir->value;
		$this->tpl->compile_dir = '/tmp/templates_c/';
		$this->tpl->force_compile = true;
		$this->tpl->assign('req',$this);
		
		$this->dispatch();
	}

	public function onReadyBlock($obj) {
		$this->html = str_replace($obj->tag,$obj->html,$this->html);
	}

	public function templateFetch($path) {
		$this->quicky->lang = $this->lang;
		return $this->tpl->fetch($path);
	}
	
	/**
	 * Called when request iterated.
	 * @return integer Status.
	 */
	public function run() {
		if (($this->jobTotal > $this->jobDone) || (sizeof($this->inner) > 0)) {
			$this->sleep(5);
		}
		
		unset($this->tpl);
		
		echo $this->html;
	}

	/**
	 * URI parser.
	 * @return void.
	 */
	public function dispatch() {	
		$e = explode('/', $_SERVER['DOCUMENT_URI'], 3);

		if (!isset($e[2])) {
			$this->lang = $this->appInstance->config->defaultlang->value;
			$this->path = '/'.$e[1];
		}
		else {
			list ($this->lang, $this->path) = $e;
			$this->path = 	rtrim($e[2], '/');
		}
		
		++$this->jobTotal;
		$this->appInstance->pages->getPage($this->lang,$this->path,array($this,'loadPage'));
	}
	
	public function loadPage($page) {
		
		++$this->jobDone;
		
		if (!$page)	{
			++$this->jobTotal;
			$this->appInstance->pages->getPage($this->lang,'/404',array($this,'loadErrorPage'));
			return;
		}
		
		$this->tpl->assign('page',	$page);
		
		$this->html = $this->templateFetch($page['template']);
		$this->appInstance->placeholders->parse($this);
	
	}
	public function loadErrorPage($page) {
		
		++$this->jobDone;
		
		if (!$page) {
			$this->html = 'Unable to load error-page.';
			$this->wakeup();
			return;
		}
		
		$this->tpl->assign('page',	$page);

		$this->html = $this->templateFetch($page['template']);
		$this->appInstance->placeholders->parse($this);
	
	}
	public function onDestruct() {
	 Daemon::log('destruct');
	}
}

