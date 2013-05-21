<?php
namespace WakePHP\ORM;

use PHPDaemon\Clients\Mongo\Collection;
use WakePHP\Core\ORM;

class ExternalAuthTokens extends ORM {

	/** @var  Collection */
	protected $externalAuthTokens;

	public function init() {
		$this->externalAuthTokens = $this->appInstance->db->{$this->appInstance->dbname . '.externalSignupRequests'};
		$this->externalAuthTokens->ensureIndex(['code' => 1, 'email' => 1], ['unique' => true]);
	}
	
	/**
	 * @param array $request
	 * @param callable|null $cb
	 */
	public function save(array $request, $cb = null) {
		if (!isset($request['_id'])) {
			$request['_id'] = new \MongoId();
		}
		$this->externalAuthTokens->upsert(['_id' => $request['_id']], $request, false, $cb);
	}

	public function getRequestById($id, $cb = null) {
		$this->externalSignupRequests->findOne($cb, ['where' => ['_id' => new \MongoId($id)]]);
	}

	public function getRequestByEmail($email, $cb = null) {
		$this->externalSignupRequests->findOne($cb, ['where' => ['email' => $email]]);
	}

	public function deleteById($id, $cb = null) {
		$this->externalSignupRequests->remove(['where' => ['_id' => new \MongoId($id)]], $cb);
	}

	public function remove(array $cond, $cb = null) {
		$this->externalSignupRequests->remove($cond, $cb);
	}

	public function getRequestByCredentials(array $credentials, $cb = null) {
		$this->externalSignupRequests->findOne($cb, ['credentials' => ['$elemMatch' => $credentials]]);
	}
}