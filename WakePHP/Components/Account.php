<?php
namespace WakePHP\Components;

use PHPDaemon\Clients\HTTP\Pool as HTTPClient;
use PHPDaemon\Clients\Mongo\Cursor;
use PHPDaemon\Core\ComplexJob;
use PHPDaemon\Utils\Crypt;
use PHPDaemon\Core\Daemon;
use PHPDaemon\Core\Debug;
use PHPDaemon\Request\Generic as Request;
use PHPDaemon\Utils\Encoding;
use WakePHP\Core\Component;
use WakePHP\Core\DeferredEventCmp;
use WakePHP\Core\Request as WakePHPRequest;

/**
 * Account component
 * @method onSessionStart(callable $cb)
 * @method onAuth(callable $cb)
 */
class Account extends Component {
	use \PHPDaemon\Traits\StaticObjectWatchdog;
	/**
	 * @return callable
	 */
	public function onAuthEvent() {
		return function ($authEvent) {
			/** @var DeferredEventCmp $authEvent */
			$this->req->onSessionRead(function ($sessionEvent) use ($authEvent) {
				if (isset($this->req->account)) {
					$authEvent->setResult();
					return;
				}
				$cb = function ($account) use ($authEvent) {
					if ($account) {
						$account['logged'] = $account['username'] !== 'Guest';
					}
					$this->req->account = $account;
					$this->req->propertyUpdated('account');
					$authEvent->setResult();
				};
				if (isset($_SESSION['accountId'])) {
					$this->appInstance->accounts->getAccountById($_SESSION['accountId'], function ($account) use ($authEvent, $cb) {
						if (!$account->exists()) {
							$this->appInstance->accounts->getAccountByName('Guest', $cb);
							return;
						}
						if (isset($account['ttlSession']) && $_SESSION['ttl'] !== $account['ttlSession']) {
							$_SESSION['ttl'] = $account['ttlSession'];
							$this->req->updatedSession = true;
						}
						$this->req->sessionKeepalive();
						$cb($account);
					});
				}
				else {
					$this->appInstance->accounts->getAccountByName('Guest', $cb);
				}
			});
		};
	}

	/**
	 * @param callable $cb
	 */
	public function getRecentSignupsCount($cb) {
		$this->appInstance->accounts->getRecentSignupsFromIP($_SERVER['REMOTE_ADDR'], $cb);
	}

	/**
	 * @param string $email
	 * @return string
	 */
	protected function getConfirmationCode($email) {
		return substr(md5($email . "\x00"
						  . $this->req->appInstance->config->cryptsalt->value . "\x00"
						  . microtime(true) . "\x00"
						  . mt_rand(0, mt_getrandmax()))
			, 0, 6);
	}

	/**
	 * @return bool
	 */
	public function checkReferer() {
		return true;
		if ($this->req->controller === 'ExternalAuthRedirect') {
			return true;
		}
		elseif ($this->req->controller === 'ExternalAuth') { // @todo: //
			return true;
		}
		if ($this->req->controller === 'ExtAuth') {
			return true;
		}
		if ($this->req->controller === 'ExtAuthPing') {
			return true;
		}
		if ($this->req->controller === 'GenKeccak') {
			return true;
		}
		if ($this->req->controller === 'Test') {
			return true;
		}
		if ($this->req->controller === 'ChangePhone') {
			return true;
		}
		if ($this->req->controller === 'UsernameAvailablityCheck') {
			return true;
		}
		return $this->req->checkDomainMatch();
	}

	/**
	 * @param $account
	 * @param null $cb
	 */
	public function loginAs($account, $cb = null) {
		if ($account) {
			$_SESSION['accountId']     = $account['_id'];
			$_SESSION['ltime'] = time();
		} else {
			unset($_SESSION['accountId'], $_SESSION['ltime']);
		}
		$this->req->updatedSession = true;
		if ($cb !== null) {
			call_user_func($cb);
		}
	}

	/**
	 * @param $ns
	 * @param $id
	 * @param $add
	 * @param $cb
	 */
	public function acceptUserAuthentication($ns, $id, $add, $cb) {
		$this->req->onSessionStart(function () use ($ns, $id, $add, $cb) {
			$crd = ['ns' => $ns, 'id' => $id];
			Daemon::log(Debug::dump($crd));
			$this->appInstance->accounts->getAccount(['credentials' => ['$elemMatch' => $crd]],
				function ($account) use ($ns, $id, $cb, $crd, $add) {
					if ($account->exists()) {
						$this->loginAs($account, $cb);
						return;
					}
					if (!isset($add['email'])) {
						$_SESSION['extAuth']       = $crd;
						$_SESSION['extAuthAdd']    = $add;
						$this->req->updatedSession = true;
						$this->req->redirectTo($this->req->getBaseUrl() . '/' . $this->req->locale . '/account/finishSignup');
						return;
					}
					$this->appInstance->accounts->getAccountByEmail($add['email'], function ($account) use ($crd, $add, $cb) {
							if ($account->exists()) {
								$this->appInstance->accounts->addCredentialsToAccount($account, $crd, function () use ($account, $cb) {
									$this->loginAs($account, $cb);
								});
								return;
							}

							$newAccount = $this->appInstance->accounts->getAccountBase($this->req);
							foreach ($add as $k => $v) {
								if (!isset($crd[$k])) {
									$crd[$k] = $v;
								}
							}
							if (isset($crd['email'])) {
								$newAccount['email'] = $crd['email'];
							}
							$newAccount['credentials'] = [$crd,];
							$this->appInstance->accounts->saveAccount($newAccount, function () use ($add, $cb) {
								$this->appInstance->accounts->getAccountByEmail($add['email'], function ($account) use ($cb) {
									$this->loginAs($account, $cb);
								});
							});
						}
					);
				});
		});
	}
}
