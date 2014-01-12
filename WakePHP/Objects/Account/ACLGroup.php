<?php
namespace WakePHP\Objects\Account;

use WakePHP\Objects\Generic;

/**
 * Class ACLGroup
 * @package WakePHP\Objects\Account
 */
class ACLGroup extends Generic {

	protected function construct() {
		$this->col = $this->orm->aclgroups;

	}

	public static function ormInit($orm) {
		parent::ormInit($orm);
		$orm->aclgroups = $orm->appInstance->db->{$orm->appInstance->dbname . '.aclgroups'};
		$orm->aclgroups->ensureIndex(array('name' => 1),array('unique' => true));
	}
}
