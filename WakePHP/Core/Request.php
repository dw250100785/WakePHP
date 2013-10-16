<?php
namespace WakePHP\Core;

use PHPDaemon\Clients\HTTP\Pool as HTTPClient;
use PHPDaemon\Core\ClassFinder;
use PHPDaemon\Core\ComplexJob;
use PHPDaemon\Core\Daemon;
use PHPDaemon\Core\Debug;
use PHPDaemon\Request\IRequestUpstream;
use PHPDaemon\Request\RequestHeadersAlreadySent;
use PHPDaemon\Structures\StackCallbacks;
use WakePHP\Blocks\Block;
use WakePHP\Utils\Array2XML;

/**
 * Request class.
 * @property array session
 * @dynamic_fields
 */
class Request extends \PHPDaemon\HTTPRequest\Generic {
	use \WakePHP\Core\Traits\Sessions;
	use \WakePHP\Core\Traits\Datetime;
	use \WakePHP\Core\Traits\Blocks;
	use \WakePHP\Core\Traits\URLToolkit;

	public $locale;
	public $path;
	public $pathArg = array();
	public $pathArgType = array();
	public $html;
	public $inner = array();
	public $startTime;
	public $req;
	public $layoutTs;
	public $jobTotal = 0;
	public $jobDone = 0;
	/** @var \Quicky */
	public $tpl;
	/** @var Components */
	public $components;
	public $dispatched = false;
	public $updatedSession = false;
	public $xmlRootName = 'response';
	/** @var  BackendClientConnection */
	public $backendClientConn;
	public $backendClientCbs;
	public $backendClientInited = false;
	/** @var  BackendServerConnection */
	public $backendServerConn;
	/** @var Block[] */
	public $queries = [];
	public $queriesCnt = 0;
	public $readyBlocks = 0;
	public $rid;
	public $account;
	private static $emulMode = false;
	/** @var  WakePHP */
	public $appInstance;
	public $cmpName;
	public $controller;
	public $dataType;
	/** @var ComplexJob */
	public $job;
	protected $theme;

	public $sPrefix;

	public $pjax;
	public $extra;

	/**
	 * Constructor
	 * @param WakePHP $appInstance
	 * @param IRequestUpstream $upstream.
	 * @param $parent
	 * @return \WakePHP\Core\Request
	 */
	public function __construct($appInstance, $upstream, $parent = null) {
		if (self::$emulMode) {
			return;
		}
		parent::__construct($appInstance, $upstream, $parent);
	}
	
	public function init() {
		try {
			$this->header('Content-Type: text/html');
		} catch (RequestHeadersAlreadySent $e) {
		}

		$this->theme = $this->appInstance->config->defaulttheme->value;

		$this->components = new Components($this);

		$this->startTime = microtime(true);

		$this->tpl = $this->appInstance->getQuickyInstance();
		$this->tpl->assign('req', $this);
	}

	public function getIp() {
		$s = &$this->attrs->server;
		$ip = $s['REMOTE_ADDR'];
		$for = '';
		if (isset($s['HTTP_CLIENT_IP'])) {
			$for = $s['HTTP_CLIENT_IP'];
		} elseif (isset($s['HTTP_X_FORWARDED_FOR'])) {
			$for = $s['HTTP_X_FORWARDED_FOR'];
		} elseif (isset($s['HTTP_VIA'])) {
			$for = $s['HTTP_VIA'];
		}
		return $ip . ($for !== '' ? ' for ' . $for : '');
	}

	/**
	 * @param $prop
	 */
	public function propertyUpdated($prop) {
		if ($this->backendServerConn) {
			$this->backendServerConn->propertyUpdated($this, $prop, $this->{$prop});
		}
	}

	/**
	 * @return \stdClass
	 */
	public function exportObject() {
		$req        = new \stdClass;
		$req->attrs = $this->attrs;
		return $req;
	}

	public function handleException($e) {
		if ($this->cmpName !== null) {
			$this->setResult(['exception' => ['type' => ClassFinder::getClassBasename($e), 'code' => $e->getCode(), 'msg' => $e->getMessage()]]);
			return true;
		}
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

		echo $this->html;
	}

	/**
	 * URI parser.
	 * @return void.
	 */
	public function dispatch() {
		$this->dispatched = true;
		$e                = explode('/', substr($_SERVER['DOCUMENT_URI'], 1), 2);
		if (($e[0] === 'component') && isset($e[1])) {

			$this->locale = Request::getString($this->attrs->request['LC']);
			if (!in_array($this->locale, $this->appInstance->locales, true)) {
				$this->locale = $this->appInstance->config->defaultlocale->value;
			}

			$e = explode('/', substr($_SERVER['DOCUMENT_URI'], 1), 5);
			++$this->jobTotal;
			$this->cmpName    = $e[1];
			$this->controller = isset($e[2]) ? $e[2] : '';
			$this->dataType   = isset($e[3]) ? $e[3] : 'json';
			$this->extra   = isset($e[4]) ? $e[4] : null;
			if ($cmp = $this->components->{$this->cmpName}) {
				$method = $this->controller . 'Controller';
				if (!$cmp->checkReferer()) {
					$this->setResult(array('errmsg' => 'Unacceptable referer.'));
					return;
				}
				if (method_exists($cmp, $method)) {
					$cmp->$method();
				}
				else {
					$cmp->defaultControllerHandler($this->controller);
				}
			}
			else {
				$this->setResult(array('errmsg' => 'Unknown component.'));
			}
			return;
		}
		$this->sessionKeepalive();
		$this->locale = $e[0];
		$this->path   = '/' . (isset($e[1]) ? $e[1] : '');
		$this->pjax = isset($_SERVER['HTTP_X_PJAX']);
		if (!in_array($this->locale, $this->appInstance->locales, true)) {
			$this->locale = $this->appInstance->config->defaultlocale->value;
			if ($this->path !== '/') {
				try {$this->redirectTo('/' . $this->locale . $this->path);} catch (RequestHeadersAlreadySent $e) {}
				return;
			}
		}
		$req        = $this;
		$this->path = preg_replace_callback('~/([a-z\d]{24})(?=/|$)~', function ($m) use ($req) {
			$type  = '';
			$value = null;
			if (isset($m[1]) && $m[1] !== '') {
				$type  = 'id';
				$value = $m[1];
			}
			$req->pathArgType[] = $type;
			$req->pathArg[]     = $value;
			return '/%' . $type;
		}, $this->path);

		if ($this->backendServerConn) {
			return;
		}

		++$this->jobTotal;
		$this->appInstance->blocks->getBlock(array(
												 'theme' => $this->theme,
												 'path'  => $this->path,
											 ), array($this, 'loadPage'));
	}

	public function setResult($result = NULL) {
		if ($this->dataType === 'json') {
			try {
				$this->header('Content-Type: text/json');
			} catch (RequestHeadersAlreadySent $e) {
			}
			$this->html = json_encode($result);
		}
		elseif ($this->dataType === 'xml') {
			$converter = new Array2XML();
			$converter->setRootName($this->xmlRootName);
			try {
				$this->header('Content-Type: text/xml');
			} catch (RequestHeadersAlreadySent $e) {
			}
			$this->html = $converter->convert($result);
		}
		elseif ($this->dataType === 'bson') {
			try {
				$this->header('Content-Type: application/octet-stream');
			} catch (RequestHeadersAlreadySent $e) {
			}
			$this->html = bson_encode($result);
		}
		else {
			$this->header('Content-Type: application/x-javascript');
			$this->html = json_encode(['errmsg' => 'Unsupported data-type.']);
		}
		++$this->jobDone;
		$this->wakeup();
	}

	public function onFinish() {
		if ($this->backendClientConn) {
			$this->backendClientConn->endRequest($this);
			unset($this->backendClientConn);
		}

		if ($this->components !== null) {
			$this->components->cleanup();
			$this->components = null;
		}

		if ($this->tpl !== null) {
			$this->tpl->assign('req', null);
			$this->tpl = null;
		}
		Daemon::log('onFinish -- ' . $_SERVER['REQUEST_URI']);
	}

	public function __destruct() {
		Daemon::log('destruct - ' . $this->attrs->server['REQUEST_URI']);
	}

	public function cacheControl($date = false) {
		$this->header('Cache-Control: no-cache, no-store, must-revalidate');
		$this->header('Pragma: no-cache');
		$this->header('Expires: Sat, 26 Jul 1997 05:00:00 GMT');
	}

	/**
	 * @param $url
	 */
	public function redirectTo($url, $finish = true, $perm = false) {
		$e = null;
		try {
			$url = HTTPClient::buildUrl($url);
			if (substr($url, 0, 1) === '/') {
				$url = $this->getBaseUrl() . $url;
			}
			if ($perm) {
				$this->status(301);
				$this->header('Location: ' . $url);
			} else {
				$this->status(302);
				$this->header('Cache-Control: no-cache, no-store, must-revalidate');
				$this->header('Pragma: no-cache');
				$this->header('Expires: Sat, 26 Jul 1997 05:00:00 GMT');
				$this->header('Location: ' . $url);
			}
		}  catch (RequestHeadersAlreadySent $e) {}
		if ($finish) {
			$this->finish();
		}
		if ($e) {
			throw $e;
		}
	}
}
