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
		$appInstance->placeholders = new Placeholders($this);
		$appInstance->pages = new Pages($this);
	}

	protected function getConfigDefaults() {
		return array(
			'templatedir' => './templates/',
		);
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

