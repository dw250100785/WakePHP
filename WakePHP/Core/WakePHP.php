<?php
namespace WakePHP\Core;

use PHPDaemon\Core\AppInstance;
use PHPDaemon\Core\Daemon;
use WakePHP\ORM\AccountsORM;
use WakePHP\ORM\SessionsORM;

/**
 * Main class of application (Quicky, MongoClient, ...)
 */
class WakePHP extends AppInstance {

	public $statistics;
	public $blocks;
	/** @var AccountsORM */
	public $accounts;
	/** @var SessionsORM */
	public $sessions;
	public $db;
	public $dbname;
	public $LockClient;
	public $locales;
	public $ipcId;
	public $jobManager;
	public $components;
	public $backendServer;
	public $backendClient;
	/** @var \HTTPClient */
	public $httpclient;

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
		$appInstance             = $this;
		$appInstance->db         = \PHPDaemon\Clients\Mongo\Pool::getInstance();
		$appInstance->dbname     = $this->config->dbname->value;
		$appInstance->ipcId      = sprintf('%x', crc32(Daemon::$process->getPid() . '-' . microtime(true) . '-' . mt_rand(0, mt_getrandmax())));
		$appInstance->JobManager = new JobManager($this);
		$appInstance->Sendmail   = new Sendmail($this);
		if (isset($this->config->BackendServer)) {
			$this->backendServer = BackendServer::getInstance($this->config->BackendServer, true, $this);
		}
		if (isset($this->config->BackendClient)) {
			$this->backendClient = BackendClient::getInstance($this->config->BackendClient, true, $this);
		}

		foreach (glob($appInstance->config->ormdir->value . '*ORM.php') as $file) {
			$class         = '\\WakePHP\\ORM\\' . strstr(basename($file), '.', true);
			$prop          = lcfirst(substr($class, 0, -3));
			$this->{$prop} = new $class($this);
			Daemon::log('Class ORM: ' . $class);
		}

		$appInstance->LockClient = \PHPDaemon\Clients\Lock\Pool::getInstance();
		$appInstance->LockClient->job(get_class($this) . '-' . $this->name, true, function ($jobname, $command, $client) use ($appInstance) {
			foreach (glob($appInstance->config->themesdir->value . '*/blocks/*') as $file) {
				Daemon::$process->fileWatcher->addWatch($file, array($appInstance, 'onBlockFileChanged'));
			}
		});
		$this->locales = array_map('basename', glob($appInstance->config->localedir->value . '*', GLOB_ONLYDIR));
		if (!in_array($this->config->defaultlocale->value, $this->locales, true)) {
			$this->locales[] = $this->config->defaultlocale->value;
		}
		if (!in_array('en', $this->locales, true)) {
			$this->locales[] = 'en';
		}
		$req                     = new \stdClass; // @TODO: refactor this shit
		$req->appInstance        = $appInstance;
		$appInstance->components = new Components($req);
		foreach ($appInstance->config as $k => $c) {
			if (isset($c->run->value) && $c->run->value) {
				if (substr($k, 0, 3) == 'Cmp') {
					$appInstance->components->{substr($k, 3)};
				}
			}
		}
		$this->serializer = 'igbinary';
		$this->httpclient = \PHPDaemon\Clients\HTTP\Pool::getInstance();
	}

	public function getLocaleName($lc) {
		if (!in_array($lc, $this->locales, true)) {
			return $this->config->defaultlocale->value;
		}
		return $lc;
	}

	public function renderBlock($blockname, $variables, $cb) {
		$appInstance = $this;
		$this->blocks->getBlock(array('name' => $blockname), function ($block) use ($variables, $cb, $appInstance) {
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

	protected function getConfigDefaults() {
		return array(
			'themesdir'     => dirname(__DIR__) . '/themes/',
			'utilsdir'      => dirname(__DIR__) . '/Utils/',
			'localedir'     => dirname(__DIR__) . '/locale/',
			'storagedir'    => '/storage/',
			'ormdir'        => dirname(__DIR__) . '/ORM/',
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
	function igbinary_serialize($m) {
		return serialize($m);
	}
}

if (!function_exists('igbinary_unserialize')) {
	function igbinary_unserialize($m) {
		return unserialize($m);
	}
}
