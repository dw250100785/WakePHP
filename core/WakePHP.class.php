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

	public function init() {
		Daemon::log(get_class($this) . ' up.');
		ini_set('display_errors','On');
		$appInstance = $this;
		$appInstance->db = Daemon::$appResolver->getInstanceByAppName('MongoClient');
		$appInstance->dbname = $this->config->dbname->value;
		$appInstance->blocks = new BlocksORM($this);
		$appInstance->accounts = new AccountsORM($this);
		$appInstance->sessions = new SessionsORM($this);
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
			'dbname' => 'WakePHP',
			'defaultlocale' => 'ru',
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

