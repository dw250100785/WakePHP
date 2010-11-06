<?php
  
 /* Main class of application (Quicky, MongoClient, ...)
 */
class xEngine extends AppInstance
{
 public $quicky;
 public $statistics;
 public $db;
 public $languages = array('ru','en');
 public $dbname = 'xE';
 public function init() {
  Daemon::log('xE up.');
  ini_set('display_errors','On');
  $appInstance = $this;
  $appInstance->db = Daemon::$appResolver->getInstanceByAppName('MongoClient');
  $appInstance->quicky = new Quicky;
  $appInstance->quicky->template_dir = $this->config->templatedir->value;
  $appInstance->quicky->compile_dir = '/tmp/templates_c/';
  $appInstance->placeholders = new Placeholders($this);
 }
 protected function getConfigDefaults()
 {
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

