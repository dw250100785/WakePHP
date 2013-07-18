<?php
namespace WakePHP\ORM;
use PHPDaemon\Core\Daemon;
use PHPDaemon\Core\Debug;
use WakePHP\ORM\Generic;

/**
 * Sessions
 */
class Sessions extends Generic {
	/**
	 * @var Sessions
	 */
	protected $sessions;

	public function init() {
		$this->sessions = $this->appInstance->db->{$this->appInstance->dbname . '.sessions'};
		$this->sessions->ensureIndex(['id' => 1], ['unique' => true]);
	}

	/**
	 * @param $id
	 * @param callable $cb
	 */
	public function getSessionById($id, $cb) {
		$this->sessions->findOne($cb, array(
			'where' => array('id' => $id)
		));
	}

	/**
	 * @param array $session
	 */
	public function saveSession($session, $cb = null) {
		$this->sessions->upsertOne(['id' => $session['id']], $session, $cb);
	}

	/**
	 * @return array
	 */
	public function startSession($add = [], $cb = null) {
		$doc = array(
			'id'   => \WakePHP\Core\Crypt::randomString(),
			'ctime' => microtime(true),
		) + $add;
		$this->saveSession($doc, $cb);
		return $doc;
	}

	/**
	 * @param array $find
	 */
	public function removeSessions($find) {
		$this->sessions->remove($find);
	}
}
