<?php

/**
 * Account component
 */
class CmpAccount extends Component {
	
	
	public function onAuthEvent() {
		
		return function($authEvent) {
			$authEvent->component->onSessionReady(function($sessionEvent) use ($authEvent) {
				$authEvent->setResult();
			});
		};
	}
	public function onSessionReadyEvent() {
		
		return function($sessionEvent) {
			$sid = Request::getString($sessionEvent->component->req->attrs->cookie['SESSID']);
			if ($sid === '') {
				$session = $sessionEvent->component->appInstance->sessions->startSession();
				$sessionEvent->component->req->attrs->session = $session;
				$sessionEvent->component->req->setcookie('SESSID',(string) $session['_id']);
				$sessionEvent->setResult();
				return;
			}
			$sessionEvent->component->appInstance->sessions->getSessionById($sid,function($session) use ($sessionEvent) {
				$sessionEvent->component->req->attrs->session = $session;
				$sessionEvent->setResult();
			});
		};
	}
	
}
