<?php

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
				
				Daemon::log($session);
			if (!isset($session['accountId'])) {
				$cb(false);
				return;
			}
			$component->accounts->getAccountById($session['accountId'], function ($account) use  ($cb) {
				if (!$account) {
					$cb(false);
					return;
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
