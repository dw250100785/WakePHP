<?php
namespace WakePHP\ORM;

use PHPDaemon\Clients\Mongo\Collection;
use WakePHP\Core\ORM;

/**
 * Class ExternalAuthTokens
 * @package WakePHP\ORM
 */
class ExternalAuthTokens extends ORM {

	/** @var  Collection */
	protected $externalAuthTokens;

	/**
	 *
	 */
	public function init() {
		$this->externalAuthTokens = $this->appInstance->db->{$this->appInstance->dbname . '.externalAuthTokens'};
		$this->externalAuthTokens->ensureIndex(['extTokenHash' => 1], ['unique' => true]);
	}

	/**
	 * @param array $doc
	 * @param callable|null $cb
	 */
	public function save(array $doc, $cb = null) {
		if (!isset($doc['_id'])) {
			$doc['_id'] = new \MongoId();
		}
		$this->externalAuthTokens->upsert(['_id' => $doc['_id']], $doc, false, $cb);
	}

	/**
	 * @param string $hash
	 * @param callable $cb
	 */
	public function findByExtTokenHash($hash, $cb = null) {
		$this->externalAuthTokens->findOne($cb, ['where' => ['extTokenHash' => $hash]]);
	}

	/**
	 * @param string $hash
	 * @param callable $cb
	 */
	public function findByExtToken($str, $cb = null) {
		$this->externalAuthTokens->findOne($cb, ['where' => ['extTokenHash' => Crypt::hash($str)]]);
	}


	/**
	 * @param array $cond
	 * @param callable $cb
	 */
	public function remove(array $cond, $cb = null) {
		$this->externalAuthTokens->remove($cond, $cb);
	}
}
