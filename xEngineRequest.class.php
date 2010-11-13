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
		
		$this->tpl = $this->appInstance->getQuickyInstance();
		$this->tpl->assign('req',$this);
		$this->dispatch();
	}

	public function onReadyBlock($obj) {
		$this->html = str_replace($obj->tag,$obj->html,$this->html);
		unset($this->inner[$obj->_nid]);
	}

	public function templateFetch($template) {
			$template = eval('return function($tpl) {
			$var = &$tpl->_tpl_vars;
  $config = &$tpl->_tpl_config;
  $capture = &$tpl->_block_props[\'capture\'];
  $foreach = &$tpl->_block_props[\'foreach\'];
  $section = &$tpl->_block_props[\'section\'];
  ?>'.$template.'
		<?php };');
		ob_start();
		$this->tpl->_eval($template);
		$html = ob_get_contents();
		ob_end_clean();
		return $html;
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
		$e = explode('/', ltrim($_SERVER['DOCUMENT_URI'],'/'), 2);

		if (!isset($e[1])) {
			$this->lang = $this->appInstance->config->defaultlang->value;
			$this->path = '/'.$e[0];
		}
		else {
			list ($this->lang, $this->path) = $e;
			if ($this->path === '') {
				$this->path = '/';
			}
		}
		
		++$this->jobTotal;
		$this->appInstance->blocks->getPage($this->lang,$this->path,array($this,'loadPage'));
	}
	
	public function addBlock($block) {
		static $c = 0;
		if ((!isset($block['mod'])) || (!class_exists($class = 'Mod'.$block['mod']))) {
			$class = 'Block';
		}
		++$c;
		$block['tag'] = '<'.$c.'>';
		$block['nowrap'] = true;
		$this->html .= $block['tag'];
		new $class($block,$this);
	}
	
	public function loadPage($page) {
		
		++$this->jobDone;
		
		if (!$page)	{
			++$this->jobTotal;
			$this->appInstance->blocks->getPage($this->lang,'/404',array($this,'loadErrorPage'));
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
	public function onDestruct() {
	 Daemon::log('destruct');
	}
}

