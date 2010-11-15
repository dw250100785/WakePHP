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
		$this->sessions->findOne($cb,array(
				'where' =>	array('_id' => new MongoId($id))
		));
	}
	public function saveSession($session) {
		$this->sessions->upsert(array('_id' => new MongoId($session['_id'])), $session);
	}
	public function startSession() {
		$doc = array(
			'_id'		=> new MongoId(),
			'ctime'	=> time(),
		);
		$this->saveSession($doc);
		return $doc;
	}
	public function removeSessions($find) {
		$this->sessions->remove($find);
	}
}
