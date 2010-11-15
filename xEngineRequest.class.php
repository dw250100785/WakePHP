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
	public $components;
	public $dispatched = false;
	
	public function init() {
		$this->req = $this;
		
		$this->components = new Components($this);
		
		$this->startTime = microtime(true);
		
		$this->tpl = $this->appInstance->getQuickyInstance();
		$this->tpl->assign('req',$this);
		$this->dispatch();
	}

	public function onReadyBlock($obj) {
		$this->html = str_replace($obj->tag,$obj->html,$this->html);
		unset($this->inner[$obj->_nid]);
		$this->req->wakeup();
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
			$this->lang = $this->appInstance->config->defaultlang->value;
			$this->path = '/'.$e[0];
		}
		else {
			list ($this->lang, $this->path) = $e;
			$ee = explode('/',$this->path);
			$this->path = '/'.$ee[0];
			$this->subPath = '/'.(isset($ee[1])?$ee[1]:'');
		}
		
		++$this->jobTotal;
		$this->appInstance->blocks->getPage($this->lang,$this->path,array($this,'loadPage'));
	}
	
	public function setResult($result) {
		if ($this->dataType === 'json') {
			//$this->header('Content-Type: text/json');
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
		Daemon::log($result);
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

