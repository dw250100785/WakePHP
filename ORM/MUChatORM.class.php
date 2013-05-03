<?php
namespace WakePHP\ORM;

use WakePHP\core\ORM;

/**
 * MUChatORM
 */
class MUChatORM extends ORM {

	public function init() {
		$this->sessions = new SessionsORM($this->appInstance);
		$this->accounts = new AccountsORM($this->appInstance);
	}
	public function getAuthKey($authkey, $cb) {
		
		$component = $this;
		$this->sessions->getSessionById($authkey, function($session) use ($cb, $component) {

			if (!isset($session['accountId'])) {
				$cb(false);
				return;
			}
			$component->accounts->getAccountById($session['accountId'], function ($account) use  ($cb) {
				if (!$account) {
					$cb(false);
					return;
				}
				if (empty($account['username'])) {
					$account['username'] = strstr($account['email'], '@', true);
				}
				$cb(array(
					'username' => $account['username'],
					'tags' => array('mainroom','secondroom'),
					'su' => in_array('Superusers', $account['aclgroups']),
				));
			});
			
		});
	}
}
