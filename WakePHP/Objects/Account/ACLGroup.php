<?php
namespace WakePHP\Objects\Account;

use WakePHP\Objects\Generic;

/**
 * Class ACLGroup
 * @package WakePHP\Objects\Account
 */
class ACLGroup extends Generic {

	public function init() {

	}

	protected function fetchObject($cb) {
		$this->orm->aclgroups->findOne($cb, ['where' => $this->cond,]);
	}

	protected function saveObject($cb) {
		if (!sizeof($this->cond)) {
			if ($cb !== null) {
				call_user_func($cb, false);
			}
			return;
		}
		$this->orm->aclgroups->upsertOne($this->cond, $this->update, $cb);
		$this->update = [];
	}

	protected function countObject($cb) {
		if (!sizeof($this->cond)) {
			if ($cb !== null) {
				call_user_func($cb, false);
			}
			return;
		}
		$this->orm->aclgroups->count($this->cond);
	}

	protected function removeObject($cb) {
		if (!sizeof($this->cond)) {
			if ($cb !== null) {
				call_user_func($cb, false);
			}
			return;
		}
		$this->orm->aclgroups->remove($this->cond);
	}
}
