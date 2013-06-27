<?php
namespace WakePHP\ORM;

use WakePHP\ORM\Generic;

/**
 * MUChat
 */
class MUChat extends Generic {
	protected $sessions;
	protected $accounts;

	/**
	 *
	 */
	public function init() {
		$this->sessions = new Sessions($this->appInstance);
		$this->accounts = new Accounts($this->appInstance);
	}

	/**
	 * @param $authkey
	 * @param callable $cb
	 */
	public function getAuthKey($authkey, $cb) {

		$component = $this;
		$this->sessions->getSessionById($authkey, function ($session) use ($cb, $component) {

			if (!isset($session['accountId'])) {
				$cb(false);
				return;
			}
			$component->accounts->getAccountById($session['accountId'], function ($account) use ($cb) {
				if (!$account) {
					$cb(false);
					return;
				}
				if (empty($account['username'])) {
					$account['username'] = strstr($account['email'], '@', true);
				}
				$cb(array(
						'username' => $account['username'],
						'tags'     => array('mainroom', 'secondroom'),
						'su'       => in_array('Superusers', $account['aclgroups']),
					));
			});

		});
	}
}
