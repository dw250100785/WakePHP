<?php
namespace WakePHP\ORM;
use PHPDaemon\Core\Daemon;
use PHPDaemon\Core\Debug;
use PHPDaemon\Utils\Crypt;
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
		$this->sessions->ensureIndex(['expires' => 1], ['expireAfterSeconds' => 0]); // @TODO: questionable reasonability
	}

	/**
	 * @param $id
	 * @param callable $cb
	 */
	public function getSessionById($id, $cb) {
		$this->sessions->findOne($cb, [
			'where' => ['id' => (string) $id, 'expires' => ['$gte' => time()]]
		]);
	}

	public function getSessionsByAccount($id, $cb) {
		$this->sessions->find($cb, ['limit' => -0xFFFF, 'where' => ['accountId' => $id, 'expires' => ['$gte' => time()]]]);
	}

	public function swipeExpired() { /* it is not gonna be used because of expireAfterSeconds index */
		$this->sessions->remove(['expires' => ['$lt' => time()]]);
	}

	public function closeSessionByObjectId($id, $accountId, $cb) {
		if (is_string($id)) {
			$id = new \MongoId($id);
		}
		$this->sessions->remove(['_id' => $id, 'accountId' => $accountId], $cb);
	}

	/**
	 * @param array $session
	 */
	public function saveSession($session, $cb = null) {
		$this->sessions->updateOne(['id' => (string) $session['id']], $session, $cb);
	}

	/**
	 * @return array
	 */
	public function startSession($add = [], $cb = null) {
		$session = [
			'id'   => Crypt::randomString(),
			'ctime' => microtime(true),
		] + $add;
		$this->sessions->upsertOne(['id' => (string) $session['id']], $session, $cb);
		return $session;
	}

	/**
	 * @param array $find
	 */
	public function removeSessions($find) {
		$this->sessions->remove($find);
	}
}
