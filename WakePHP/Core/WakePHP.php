<?php
namespace WakePHP\Core;

use PHPDaemon\Core\AppInstance;
use PHPDaemon\Core\Daemon;
use PHPDaemon\Core\Debug;
use WakePHP\ORM\AccountRecoveryRequests;
use WakePHP\ORM\Accounts;
use WakePHP\ORM\ExternalAuthTokens;
use WakePHP\ORM\ExternalSignupRequests;
use WakePHP\ORM\Sessions;

/**
 * Main class of application (Quicky, MongoClient, ...)
 * @dynamic_fields
 */
class WakePHP extends AppInstance {

	/**
	 * @var
	 */
	public $statistics;
	/** @var \WakePHP\ORM\Blocks */
	public $blocks;
	/** @var Accounts */
	public $accounts;
	/** @var Sessions */
	public $sessions;
	/**
	 * @var \PHPDaemon\Clients\Mongo\Pool
	 */
	public $db;
	/**
	 * @var
	 */
	public $dbname;
	/**
	 * @var
	 */
	public $LockClient;
	/**
	 * @var
	 */
	public $locales;
	/**
	 * @var
	 */
	public $ipcId;
	/**
	 * @var
	 */
	public $jobManager;
	/**
	 * @var
	 */
	public $components;
	/** @var BackendServer */
	public $backendServer;
	/** @var  BackendClient */
	public $backendClient;
	/** @var \PHPDaemon\Clients\HTTP\Pool */
	public $httpclient;
	/** @var ExternalSignupRequests */
	public $externalSignupRequests;
	/** @var Sendmail */
	public $Sendmail;
	/** @var AccountRecoveryRequests */
	public $accountRecoveryRequests;
	/** @var ExternalAuthTokens */
	public $externalAuthTokens;
	public $JobManager;
	public $serializer;

	public function onReady() {
		if (isset($this->backendServer)) {
			$this->backendServer->onReady();
		}
		if (isset($this->backendClient)) {
			$this->backendClient->onReady();
		}
	}

	public function init() {
		Daemon::log(get_class($this) . ' up.');
		ini_set('display_errors', 'On');
		$this->db         = \PHPDaemon\Clients\Mongo\Pool::getInstance();
		$this->dbname     = $this->config->dbname->value;
		$this->ipcId      = sprintf('%x', crc32(Daemon::$process->getPid() . '-' . microtime(true) . '-' . mt_rand(0, mt_getrandmax())));
		$this->JobManager = new JobManager($this);
		$this->Sendmail   = new Sendmail($this);
		if (isset($this->config->BackendServer)) {
			$this->backendServer = BackendServer::getInstance($this->config->BackendServer, true, $this);
		}
		if (isset($this->config->BackendClient)) {
			$this->backendClient = BackendClient::getInstance($this->config->BackendClient, true, $this);
		}

		foreach (Daemon::glob($this->config->ormdir->value . '*.php') as $file) {
			$class         = strstr(basename($file), '.', true);
			if ($class === 'Generic') {
				continue;
			}
			$prop          = preg_replace_callback('~^[A-Z]+~', function ($m) {return strtolower($m[0]);}, $class);
			$class         = '\\WakePHP\\ORM\\' . $class;
			$this->{$prop} = &$a; // trick ;-)
			unset($a);
			$this->{$prop} = new $class($this);
		}

		$this->LockClient = \PHPDaemon\Clients\Lock\Pool::getInstance();
		$this->LockClient->job(get_class($this) . '-' . $this->name, true, function ($jobname, $command, $client) {
			foreach (glob($this->config->themesdir->value . '*/blocks/*') as $file) {
				Daemon::$process->fileWatcher->addWatch($file, array($this, 'onBlockFileChanged'));
			}
		});
		$this->locales = array_map('basename', glob($this->config->localedir->value . '*', GLOB_ONLYDIR));
		if (!in_array($this->config->defaultlocale->value, $this->locales, true)) {
			$this->locales[] = $this->config->defaultlocale->value;
		}
		if (!in_array('en', $this->locales, true)) {
			$this->locales[] = 'en';
		}
		$this->components = new Components($this->fakeRequest());
		foreach ($this->config as $k => $c) {
			if (isset($c->run->value) && $c->run->value) {
				if (substr($k, 0, 3) === 'Cmp') {
					$appInstance->components->{substr($k, 3)};
				}
			}
		}
		$this->serializer = 'igbinary';
		$this->httpclient = \PHPDaemon\Clients\HTTP\Pool::getInstance();
	}

	/**
	 * @param $lc
	 * @return mixed
	 */
	public function getLocaleName($lc) {
		if (!in_array($lc, $this->locales, true)) {
			return $this->config->defaultlocale->value;
		}
		return $lc;
	}

	/**
	 * @param $blockname
	 * @param $variables
	 * @param $cb
	 */
	public function renderBlock($blockname, $variables, $cb) {
		$appInstance = $this;
		$this->blocks->getBlock(['name' => $blockname], function ($block) use ($variables, $cb, $appInstance) {
			$tpl = $appInstance->getQuickyInstance();
			$tpl->assign($variables);
			$tpl->assign('block', $block);
			$tpl->register_function('getblock', array($this, 'getBlock'));
			static $cache = [];
			$k = $block['cachekey'];
			if (isset($cache[$k])) {
				$tplf = $cache[$k];
			}
			else {
				$tplf      = eval($block['templatePHP']);
				$cache[$k] = $tplf;
			}
			ob_start();
			call_user_func($tplf, $tpl);
			$r = ob_get_contents();
			ob_end_clean();
			$cb($r);
		});
	}

	protected function fakeRequest() { // @TODO: refactor this shit
		$req = new \stdClass;
		$req->appInstance = $this;
		return $req;
	}

	/**
	 * @param $file
	 */
	public function onBlockFileChanged($file) {
		Daemon::log('changed - ' . $file);
		$blockName = pathinfo($file, PATHINFO_FILENAME);
		$ext       = pathinfo($file, PATHINFO_EXTENSION);
		$decoder   = function ($json) {
			static $pairs = array(
				"\n" => '',
				"\r" => '',
				"\t" => '',
			);
			return json_decode(strtr($json, $pairs), true);
		};
		if ($ext === 'obj') {
			$block          = $decoder(file_get_contents($file));
			$block['name']  = pathinfo($file, PATHINFO_FILENAME);
			$tplFilename    = dirname($file) . '/' . $block['name'] . '.tpl';
			$block['theme'] = basename(dirname($file));
			if (file_exists($tplFilename)) {
				$block['template'] = file_get_contents($tplFilename);
			}
			$this->blocks->saveBlock($block);
		}
		elseif ($ext === 'tpl') {
			Daemon::log('update');
			$this->blocks->saveBlock(array(
										 'name'     => $blockName,
										 'template' => file_get_contents($file)
									 ), true);
		}
	}

	/**
	 * @return \Quicky
	 */
	public function getQuickyInstance() {
		require_once $this->config->utilsdir->value . 'lang_om_number.php';
		$tpl = new \Quicky;
		$tpl->load_filter('pre', 'optimize');
		$tpl->template_dir                      = $this->config->themesdir->value;
		$tpl->compile_dir                       = '/tmp/templates_c/';
		$tpl->compiler_prefs['inline_includes'] = true;
		$tpl->force_compile                     = true;
		return $tpl;
	}

	/**
	 * @return array
	 */
	protected function getConfigDefaults() {
		return array(
			'themesdir'     => 'themes/',
			'utilsdir'      => 'WakePHP/Utils/',
			'localedir'     => 'locale/',
			'storagedir'    => '/storage/',
			'ormdir'        => 'WakePHP/ORM/',
			'dbname'        => 'WakePHP',
			'defaultlocale' => 'en',
			'defaulttheme'  => 'simple',
			'domain'        => 'host.tld',
			'cookiedomain'  => 'host.tld',
		);
	}

	/**
	 * Function handles incoming Remote Procedure Calls
	 * @param string $method Method name.
	 * @param array $args    Arguments.
	 * @return mixed Result
	 */
	public function RPCall($method, $args) {
		if ($method === 'saveBlock') {
			call_user_func_array(array($this->blocks, 'saveBlock'), $args);
		}
		elseif ($method === 'saveAccount') {
			call_user_func_array(array($this->accounts, 'saveAccount'), $args);
		}
		elseif ($method === 'saveACLgroup') {
			call_user_func_array(array($this->accounts, 'saveACLgroup'), $args);
		}
	}

	/**
	 * Creates Request.
	 * @param object $req      Request.
	 * @param object $upstream Upstream application instance.
	 * @return object Request.
	 */
	public function beginRequest($req, $upstream) {
		return new Request($this, $upstream, $req);
	}

	/**
	 * Handles the output from downstream requests.
	 * @param object $r Request.
	 * @param string $s The output.
	 * @return void
	 */
	public function requestOut($r, $s) {
	}

	/**
	 * Handles the output from downstream requests.
	 * @return void
	 */
	public function endRequest($req, $appStatus, $protoStatus) {
	}

}

if (!function_exists('igbinary_serialize')) {
	/**
	 * @param $m
	 * @return string
	 */
	function igbinary_serialize($m) {
		return serialize($m);
	}
}

if (!function_exists('igbinary_unserialize')) {
	/**
	 * @param $m
	 * @return mixed
	 */
	function igbinary_unserialize($m) {
		return unserialize($m);
	}
}
