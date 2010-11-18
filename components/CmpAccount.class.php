<?php

/**
 * Account component
 */
class CmpAccount extends Component {
	
	
	public function onAuthEvent() {
		
		return function($authEvent) {
			$authEvent->component->onSessionRead(function($sessionEvent) use ($authEvent) {
				$cb = function ($account) use ($authEvent) {
					if ($account) {
						$account['logged'] = $account['username'] !== 'Guest';
					}
					$authEvent->component->req->account = $account;
					$authEvent->setResult();
				};
				if (isset($authEvent->component->req->attrs->session['accountId'])) {
					$authEvent->component->appInstance->accounts->getAccountById($authEvent->component->req->attrs->session['accountId'],$cb);
				}
				else {
					$authEvent->component->appInstance->accounts->getAccountByName('Guest',$cb);
				}
			});
		};
	}
	
	public function SignupController() {
		$req = $this->req;
		$this->onSessionStart(function($sessionEvent) use ($req) {
			$req->components->CAPTCHA->validate(function($result) {
			 
			});
			$req->setResult(array('success' => true));
		});
	}
	
	
	public function LogoutController() {
		$req = $this->req;
		$this->onSessionRead(function($sessionEvent) use ($req) {
			unset($req->attrs->session['accountId']);
			$req->updatedSession = true;
			$req->setResult(array('success' => true));
		});
	}
	
	public function	AuthenticationController() {
		
		$req = $this->req;
		$this->onSessionStart(function($sessionEvent) use ($req) {
			$req->appInstance->accounts->getAccount(array('$or' => array(
				array('username' => Request::getString($req->attrs->request['username'])),
				array('email' => Request::getString($req->attrs->request['username']))
			))
			,function ($account) use ($req) {
				if (!$account) {
					$req->setResult(array('success' => false, 'errors' => array(
						'username' => 'Unrecognized username.'
					)));
				}
				elseif ($req->appInstance->accounts->checkPassword($account, Request::getString($req->attrs->request['password']))) {
					$req->attrs->session['accountId'] = $account['_id'];
					$req->updatedSession = true;
					$req->setResult(array('success' => true));
				}
				else {
					$req->setResult(array('success' => false, 'errors' => array(
						'password' => 'Invalid password.'
					)));
				}
			});
		});
	}
	
	
	public function startSession() {
		$session = $this->appInstance->sessions->startSession();
		$this->req->attrs->session = $session;
		$sid = (string) $session['_id'];
		$this->req->setcookie('SESSID', $sid, time() + 60*60*24*365, '/');
	}
	public function onSessionStartEvent() {
		
		return function($sessionStartEvent) {
			if (isset($sessionStartEvent->component->req->session['_id'])) {
				$sessionStartEvent->setResult();
				return;
			}
			$sid = Request::getString($sessionStartEvent->component->req->attrs->cookie['SESSID']);
			if ($sid === '') {
				$sessionStartEvent->component->startSession();
				$sessionStartEvent->setResult();
				return;
			}
			
			$sessionStartEvent->component->onSessionRead(function($session) use ($sessionStartEvent) {
			
				if (!$session) {
					$sessionStartEvent->component->startSession();
				}
				$sessionStartEvent->setResult();
			});	
		};
	}
	public function onSessionReadEvent() {
		
		return function($sessionEvent) {
			$sid = Request::getString($sessionEvent->component->req->attrs->cookie['SESSID']);
			if ($sid === '') {
				$sessionEvent->setResult();
				return;
			}
			$sessionEvent->component->appInstance->sessions->getSessionById($sid, function($session) use ($sessionEvent) {
				if ($session) {
					$sessionEvent->component->req->attrs->session = $session;
				}
				$sessionEvent->setResult();
			});
		};
	}
}
