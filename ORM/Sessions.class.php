<?php

/**
 * Sessions
 */
class Sessions extends ORM {
	public $sessions;

	public function init() {
		$this->sessions = $this->appInstance->db->{$this->appInstance->dbname . '.sessions'};
	}
	public function getSessionById($id,$cb) {
		$this->getAccount(array('username' => $username), $cb);
	}
	public function saveSession($session) {
		$this->accounts->upsert(array('_id' => $session['_id']),array('$set' => $session));
	}
	public function startSession() {
		$doc = array(
			'_id'		=> MongoId(),
			'ctime'	=> time(),
		);
		$this->sessions->insert($doc);
		return $doc;
	}
	public function removeSessions($find) {
		$this->sessions->remove($find);
	}
}
