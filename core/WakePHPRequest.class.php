<?php

/**
 * Request class.
 */
class WakePHPRequest extends HTTPRequest {

	public $locale;
	public $path;
	public $pathArg = array();
	public $pathArgType = array();
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
	public $xmlRootName = 'response';
	
	public function init() {
		try {
			$this->header('Content-Type: text/html');
		} catch (RequestHeadersAlreadySent $e) {}
		
		$this->req = $this;
		
		$this->theme = $this->appInstance->config->defaulttheme->value;
		
		$this->components = new Components($this);
		
		$this->startTime = microtime(true);
		
		$this->tpl = $this->appInstance->getQuickyInstance();
		$this->tpl->assign('req',$this);
	}

	public function date($format, $ts = null) { // @todo
    if ($ts === null) {
      $ts = time();
    }
		$t = array();
		$format = preg_replace_callback('~%n2?~', function($m) use (&$t) {
			$t[] = $m[0];
			return "\x01";
		}, $format);
		$r = date($format, $ts);
    $req = $this;
    $r = preg_replace_callback('~\x01~s', function($m) use ($t, $ts, $req) {
      static $i = 0;
      switch ($t[$i++]) {
        case "%n":
          return $req->monthes[date('n', $ts)];
        case "%n2":
          return $req->monthes2[date('n', $ts)];
      }
    }, $r);
    return $r;
	}
	
	public function date_period($st,$fin) {
		if ((is_int($st)) || (ctype_digit($st))) {$st = $this->date('d-m-Y-H-i-s',$st);}
		$st = explode('-',$st);
		if ((is_int($fin)) || (ctype_digit($fin))) {$fin = $this->date('d-m-Y-H-i-s',$fin);}
		$fin = explode('-',$fin);
		if (($seconds = $fin[5] - $st[5]) < 0) {$fin[4]--; $seconds += 60;}
		if (($minutes = $fin[4] - $st[4]) < 0) {$fin[3]--; $minutes += 60;}
		if (($hours = $fin[3] - $st[3]) < 0) {$fin[0]--; $hours += 24;}
		if (($days = $fin[0] - $st[0]) < 0) {$fin[1]--; $days += $this->date('t', mktime(1, 0, 0, $fin[1], $fin[0], $fin[2]));}
		if (($months = $fin[1] - $st[1]) < 0) {$fin[2]--; $months += 12;}
		$years = $fin[2] - $st[2];
		return array($seconds,$minutes,$hours,$days,$months,$years);
	}


	public function strtotime($str) {
		return Strtotime::parse($str);
	}
	
	public function onReadyBlock($obj) {
		$this->html = str_replace($obj->tag, $obj->html, $this->html);
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

	public function checkDomainMatch($domain = null, $pattern = null) {
		if ($domain === null) {
			$domain = parse_url(Request::getString($this->attrs->server['HTTP_REFERER']), PHP_URL_HOST);
		}
		if ($pattern === null) {
			$pattern = $this->appInstance->config->cookiedomain->value;
		}
		foreach (explode(', ',$pattern) as $part) {
			if (substr($part, 0, 1) === '.') {
				if ('.' . ltrim(substr($domain, -strlen($part)), '.') === $part) {
					return true;
				}
			} else {
				if ($domain === $part) {
					return true;
				}
			}	
		}
		return false;
	}
	
	/**
	 * URI parser.
	 * @return void.
	 */
	public function dispatch() {	
		$this->dispatched = true;
		$e = explode('/', substr($_SERVER['DOCUMENT_URI'], 1), 2);
		if (($e[0] === 'component') && isset($e[1])) {
		
			$e = explode('/', substr($_SERVER['DOCUMENT_URI'], 1), 4);
			++$this->jobTotal;
			$this->cmpName = $e[1];
			$this->controller = isset($e[2])?$e[2]:'';
			$this->dataType = isset($e[3])?$e[3]:'json';
			if ($this->components->{$this->cmpName}) {
				$method = $this->controller.'Controller';
				if (!$this->components->{$this->cmpName}->checkReferer()) {
					$this->setResult(array('errmsg' => 'Unacceptable referer.'));
				}
				if (method_exists($this->components->{$this->cmpName},$method)) {
					$this->components->{$this->cmpName}->$method();
				}
				else {
					$this->setResult(array('errmsg' => 'Unknown controller.'));
				}
			}
			else {
				$this->setResult(array('errmsg' => 'Unknown component.'));
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
			if (!in_array($this->locale, $this->appInstance->locales, true)) {
				try {
					$this->header('Location: /' . $this->appInstance->config->defaultlocale->value . $this->path);
				}
				catch (RequestHeadersAlreadySent $e) {}
				$this->finish();
				return;
			}
			$req = $this;
			$this->path = preg_replace_callback('~/([a-z\d]{24})(?=/|$)~', function($m) use ($req) {
				if (isset($m[1]) && $m[1] !== '') {
					$type = 'id';
					$value = $m[1];
				}
				$req->pathArgType[] = $type;
				$req->pathArg[] = $value;
				return '/%'.$type;
			}, $this->path);

		}
		
		++$this->jobTotal;
		$this->appInstance->blocks->getBlock(array(
			'theme' => $this->theme,
			'path' => $this->path,
		), array($this, 'loadPage'));
	}
	
	public function setResult($result) {
		if ($this->dataType === 'json') {
			try {
				$this->header('Content-Type: text/json');
			}
			catch (RequestHeadersAlreadySent $e) {}
			$this->html = json_encode($result);
		}
		elseif ($this->dataType === 'xml') {
			$converter = new Array2XML();
			$converter->rootName = $this->xmlRootName;
			try {
				$this->header('Content-Type: text/xml');
			}
			catch (RequestHeadersAlreadySent $e) {}
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
		if ((!isset($block['type'])) || (!class_exists($class = 'Block' . $block['type']))) {
			$class = 'Block';
		}
		$block['tag'] = new MongoId();
		$block['nowrap'] = true;
		$this->html .= $block['tag'];
		new $class($block, $this);
	}
	
	public function loadPage($page) {
		
		++$this->jobDone;
		
		if (!$page)	{
			++$this->jobTotal;
			try {
				$this->header('404 Not Found');
			}
			catch (RequestHeadersAlreadySent $e) {}
			$this->appInstance->blocks->getBlock(array(
				'theme' => $this->theme,
				'path' => '/404',
			), array($this, 'loadErrorPage'));
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
	 Daemon::log('destruct - '.$this->path);
	}
}
