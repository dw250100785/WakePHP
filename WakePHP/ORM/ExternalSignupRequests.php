<?php
namespace WakePHP\ORM;

use PHPDaemon\Clients\Mongo\Collection;
use WakePHP\Core\ORM;

/**
 * Class ExternalSignupRequests
 * @package WakePHP\ORM
 */
class ExternalSignupRequests extends ORM {

	/** @var  Collection */
	protected $externalSignupRequests;

	/**
	 *
	 */
	public function init() {
		$this->externalSignupRequests = $this->appInstance->db->{$this->appInstance->dbname . '.externalSignupRequests'};
		$this->externalSignupRequests->ensureIndex(['code' => 1, 'email' => 1], ['unique' => true]);
	}

	/**
	 * @param array $request
	 * @param callable|null $cb
	 */
	public function save(array $request, $cb = null) {
		if (!isset($request['_id'])) {
			$request['_id'] = new \MongoId();
		}
		$this->externalSignupRequests->upsert(['_id' => $request['_id']], $request, false, $cb);
	}

	/**
	 * @param $id
	 * @param callable $cb
	 */
	public function getRequestById($id, $cb = null) {
		$this->externalSignupRequests->findOne($cb, ['where' => ['_id' => new \MongoId($id)]]);
	}

	/**
	 * @param string $email
	 * @param callable $cb
	 */
	public function getRequestByEmail($email, $cb = null) {
		$this->externalSignupRequests->findOne($cb, ['where' => ['email' => $email]]);
	}

	/**
	 * @param $id
	 * @param callable $cb
	 */
	public function deleteById($id, $cb = null) {
		$this->externalSignupRequests->remove(['where' => ['_id' => new \MongoId($id)]], $cb);
	}

	/**
	 * @param array $cond
	 * @param callable $cb
	 */
	public function remove(array $cond, $cb = null) {
		$this->externalSignupRequests->remove($cond, $cb);
	}

	/**
	 * @param array $credentials
	 * @param callable $cb
	 */
	public function getRequestByCredentials(array $credentials, $cb = null) {
		$this->externalSignupRequests->findOne($cb, ['credentials' => ['$elemMatch' => $credentials]]);
	}
}
