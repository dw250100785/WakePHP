<?php
namespace WakePHP\Components\MUChat;

use PHPDaemon\Request\Generic as Request;

class IdleCheck extends Request {
	public function run() {
		$appInstance = $this->appInstance;
		$this->appInstance->db->{$this->appInstance->config->dbname->value . '.muchatsessions'}->find(function ($cursor) use ($appInstance) {
			$users = array();
			foreach ($cursor->items as $sess) {
				$users[] = $sess['username'];
			}
			$appInstance->kickUsers($users, '', 'Idle');
			$cursor->destroy();
		}, array(
			   'where' => array(
				   'mtime'    => array('$lt' => microtime(true) - $this->appInstance->idleTimeout),
				   'worker'   => $this->appInstance->ipcId,
				   'username' => array('$exists' => true)
			   )
		   ));
		$this->sleep(5);
	}
}
