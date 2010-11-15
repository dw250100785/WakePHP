<?php

/**
 * Account component
 */
class CmpAccount extends Component {
	
	
	public function onAuthEvent() {
		
		return function($authEvent) {
			$authEvent->component->onSessionReady(function($sessionEvent) use ($authEvent) {
				$cb = function ($account) use ($authEvent) {
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
	
	public function	AuthenticationController() {
		
		$req = $this->req;
		$this->req->appInstance->accounts->getAccount(array('$or' => array(
					array('username' => Request::getString($this->req->attrs->request['username'])),
					array('email' => Request::getString($this->req->attrs->request['username']))
		))
		,function ($account) use ($req, $id) {

			if ($req->appInstance->accounts->checkPassword($account, Request::getString($this->req->attrs->request['password']))) {
				$req->attrs->session['accountId'] = $account['_id'];
				$req->updatedSession = true;
				
			}
			$req->setResult(array(''));
		});
	}
	
	
	public function startSession() {
		$session = $this->appInstance->sessions->startSession();
		$this->req->attrs->session = $session;
		$this->req->setcookie('SESSID', (string) $session['_id']);
	}
	public function onSessionReadyEvent() {
		
		return function($sessionEvent) {
			$sid = Request::getString($sessionEvent->component->req->attrs->cookie['SESSID']);
			if ($sid === '') {
				$sessionEvent->component->startSession();
				$sessionEvent->setResult();
				return;
			}
			$sessionEvent->component->appInstance->sessions->getSessionById($sid,function($session) use ($sessionEvent) {
				
				if (!$session) {
					$sessionEvent->component->startSession();
				}
				else {
					$sessionEvent->component->req->attrs->session = $session;
				}
				$sessionEvent->setResult();
			});
		};
	}
	
}
