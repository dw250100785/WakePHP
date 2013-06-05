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
	public function saveSession($session) {
		$this->sessions->upsert(['id' => $session['id']], $session);
	}

	/**
	 * @return array
	 */
	public function startSession($add = []) {
		$doc = array(
			'id'   => \WakePHP\Core\Crypt::randomString(),
			'ctime' => time(),
		) + $add;
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
