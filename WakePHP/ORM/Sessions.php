<?php
namespace WakePHP\ORM;

use WakePHP\Core\ORM;

/**
 * Sessions
 */
class Sessions extends ORM {
	/**
	 * @var Sessions
	 */
	protected $sessions;

	public function init() {
		$this->sessions = $this->appInstance->db->{$this->appInstance->dbname . '.sessions'};
	}

	/**
	 * @param $id
	 * @param callable $cb
	 */
	public function getSessionById($id, $cb) {
		$this->sessions->findOne($cb, array(
			'where' => array('_id' => new \MongoId($id))
		));
	}

	/**
	 * @param array $session
	 */
	public function saveSession($session) {
		$this->sessions->upsert(array('_id' => new \MongoId($session['_id'])), $session);
	}

	/**
	 * @return array
	 */
	public function startSession() {
		$doc = array(
			'_id'   => new \MongoId(),
			'ctime' => time(),
		);
		$this->saveSession($doc);
		return $doc;
	}

	/**
	 * @param array $find
	 */
	public function removeSessions($find) {
		$this->sessions->remove($find);
	}
}
