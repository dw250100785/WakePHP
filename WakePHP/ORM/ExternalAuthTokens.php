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
		if (!isset($doc['extTokenHash'])) {
			if ($cb !== null) {
				call_user_func($cb, false);
			}
			return;
		}
		$this->externalAuthTokens->upsert(['extTokenHash' => $doc['extTokenHash']], ['$set' => $doc], false, $cb);
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
		$this->externalAuthTokens->findOne($cb, ['where' => ['extTokenHash' => \WakePHP\Core\Crypt::hash($str)]]);
	}

	/**
	 * @param string $hash
	 * @param callable $cb
	 */
	public function findByIntToken($str, $cb = null) {
		$this->externalAuthTokens->findOne($cb, ['where' => ['intToken' => $str]]);
	}


	/**
	 * @param array $cond
	 * @param callable $cb
	 */
	public function remove(array $cond, $cb = null) {
		$this->externalAuthTokens->remove($cond, $cb);
	}
}
