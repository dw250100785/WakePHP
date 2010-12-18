<?php

/**
 * Main class of application (Quicky, MongoClient, ...)
 */
class WakePHP extends AppInstance {

	public $statistics;
	public $blocks;
	public $accounts;
	public $sessions;
	public $db;
	public $dbname;
	public $LockClient;
	public $locales;

	public function init() {
		Daemon::log(get_class($this) . ' up.');
		ini_set('display_errors','On');
		$appInstance = $this;
		$appInstance->db = Daemon::$appResolver->getInstanceByAppName('MongoClient');
		$appInstance->dbname = $this->config->dbname->value;
		$appInstance->blocks = new BlocksORM($this);
		$appInstance->accounts = new AccountsORM($this);
		$appInstance->sessions = new SessionsORM($this);
		$appInstance->outgoingmail = new OutgoingmailORM($this);
		$appInstance->LockClient = Daemon::$appResolver->getInstanceByAppName('LockClient');
		$appInstance->LockClient->job(get_class($this).'-'.$this->name, true, function($jobname, $command, $client) use ($appInstance)
		{
			foreach (glob($appInstance->config->themesdir->value.'*/blocks/*') as $file) {
				Daemon::$process->fileWatcher->addWatch($file, array($appInstance,'onBlockFileChanged'));
			}
		});
		$this->locales = array_map('basename', glob($appInstance->config->localedir->value.'*', GLOB_ONLYDIR));
		if (!in_array($this->config->defaultlocale->value, $this->locales, true)) {
			$this->locales[] = $this->config->defaultlocale->value;
		}
	}
	public function getLocaleName($lc) {
		if (!in_array($lc, $this->locales, true)) {
			return $this->config->defaultlocale->value;
		}
		return $lc;
	}
	public function renderBlock($blockname, $variables, $cb) {
			$appInstance = $this;
			$this->blocks->getBlockByName($blockname, function ($block) use ($variables, $cb, $appInstance) {
				$tpl = $appInstance->getQuickyInstance();
				$tpl->assign($variables);
				$cb($tpl->PHPtemplateFetch($block['templatePHP']));
			});
	}
	public function onBlockFileChanged($file) {
		Daemon::log('changed - '.$file);
		$blockName = pathinfo($file, PATHINFO_FILENAME);
		$ext = pathinfo($file, PATHINFO_EXTENSION);
		if ($ext === 'obj') {
			$block = $decoder(file_get_contents($file));
			$block['name'] = pathinfo($file,PATHINFO_FILENAME);
			$tplFilename = dirname($file).'/'.$block['name'].'.tpl';
			$block['theme'] = $theme;
			if (file_exists($tplFilename)) {
				$block['template'] = file_get_contents($tplFilename);
			}
			$this->blocks->saveBlock($block);
		}
		elseif ($ext === 'tpl') {
			Daemon::log('update');
			$this->blocks->saveBlock(array(
				'name' => $blockName,
				'template' => file_get_contents($file)
			), true);
		}
	}
	public function getQuickyInstance() {
		$tpl = new Quicky;
		$tpl->template_dir = $this->config->templatedir->value;
		$tpl->compile_dir = '/tmp/templates_c/';
		$tpl->force_compile = true;
		return $tpl;
	}
	
	protected function getConfigDefaults() {
		return array(
			'templatedir' => './templates/',
			'themesdir' =>	dirname(__DIR__).'/PackagedThemes/',
			'localedir' =>	dirname(__DIR__).'/locale/',
			'dbname' => 'WakePHP',
			'defaultlocale' => 'en',
			'domain' => 'host.tld',
		);
	}
	
	/**
	 * Function handles incoming Remote Procedure Calls
	 * @param string Method name.
	 * @param array Arguments.
	 * @return mixed Result
	 */
	public function RPCall($method, $args) {
		if ($method === 'saveBlock') {
			call_user_func_array(array($this->blocks,'saveBlock'), $args);
		}
		elseif ($method === 'saveAccount') {
			call_user_func_array(array($this->accounts,'saveAccount'), $args);
		}
		elseif ($method === 'saveACLgroup') {
			call_user_func_array(array($this->accounts,'saveACLgroup'), $args);
		}
	}

	/**
	 * Creates Request.
	 * @param object Request.
	 * @param object Upstream application instance.
	 * @return object Request.
	 */
	public function beginRequest($req, $upstream) {
		return new WakePHPRequest($this, $upstream, $req);
	}

}

