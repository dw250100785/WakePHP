<?php
namespace WakePHP\ORM;

use PHPDaemon\Clients\Mongo\Collection;
use WakePHP\Core\ORM;

class ExternalSignupRequests extends ORM {

	/** @var  Collection */
	protected $externalSignupRequests;

	/**
	 * @param array $request
	 * @param callable|null $cb
	 */
	public function save(array $request, $cb = null) {
		$this->externalSignupRequests->upsert(['_id' => new \MongoId($request['_id'])], $request, false, $cb);
	}

	public function getRequestById($id, $cb = null) {
		$this->externalSignupRequests->findOne($cb, ['where' => ['_id' => new \MongoId($id)]]);
	}

	public function deleteById($id, $cb = null) {
		$this->externalSignupRequests->remove(['where' => ['_id' => new \MongoId($id)]], $cb);
	}

	public function init() {
		$this->externalSignupRequests->ensureIndex(['code' => 1, 'email' => 1], ['unique' => true]);
	}
}
