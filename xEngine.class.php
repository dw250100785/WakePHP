<?php

/**
 * Main class of application (Quicky, MongoClient, ...)
 */
class xEngine extends AppInstance {

	public $statistics;
	public $db;
	public $dbname = 'xE';

	public function init() {
		Daemon::log('xE up.');
		ini_set('display_errors','On');
		$appInstance = $this;
		$appInstance->db = Daemon::$appResolver->getInstanceByAppName('MongoClient');
		$appInstance->blocks = new Blocks($this);
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
	}

	/**
	 * Creates Request.
	 * @param object Request.
	 * @param object Upstream application instance.
	 * @return object Request.
	 */
	public function beginRequest($req, $upstream) {
		return new xEngineRequest($this, $upstream, $req);
	}

}

