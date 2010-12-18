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
					$authEvent->component->appInstance->accounts->getAccountById($authEvent->component->req->attrs->session['accountId'], $cb);
				}
				else {
					$authEvent->component->appInstance->accounts->getAccountByName('Guest', $cb);
				}
			});
		};
	}
	
	public function getRecentSignupsCount($cb) {
		$this->appInstance->accounts->getRecentSignupsFromIP($this->req->attrs->server['REMOTE_ADDR'], $cb);
	}
	
	public function UsernameAvailablityCheckController() {
		$req = $this->req;
		$username = Request::getString($req->attrs->request['username']);
		if (($r = $this->checkUsernameFormat($username)) !== true) {
			$req->setResult(array('success' => true,  'error' => $r));
			return;
		}
		$this->appInstance->accounts->getAccountByUnifiedName($username, function($account) use ($req) {
			if ($account) {
				$req->setResult(array('success' => true, 'error' => 'Username already taken.'));
			}
			else {
				$req->setResult(array('success' => true));
			}
		});
	}
	
	public function SignupController() {
		$req = $this->req;
		$this->onSessionStart(function($sessionEvent) use ($req) {
			$job = $req->job = new ComplexJob(function($job) {
				$errors = array();
				foreach ($job->results as $name => $result) {
					if (sizeof($result) > 0) {
						$errors[$name] = $result;
					}
				}
				$req = $job->req;
				if (sizeof($errors) === 0) {
					
					$req->appInstance->accounts->saveAccount(array(
						'email' => $email = Request::getString($req->attrs->request['email']),
						'username' => Request::getString($req->attrs->request['username']),
						'location' => $city = Request::getString($req->attrs->request['location']),
						'password' => $password = Request::getString($req->attrs->request['password']),
						'confirmationcode' => substr(md5($req->attrs->request['email'] . "\x00" . microtime(true)."\x00".mt_rand(0, mt_getrandmax()), 0, 6))
						'regdate' => time(),
						'ip' => $req->attrs->server['REMOTE_ADDR'],
						'aclgroups' => array('Users'),
						'acl' => array(),
					), function ($lastError) use ($req, $email, $password, $city)
					{
						if ($city !== '') {
						
							$req->components->GMAPS->geo($city, function ($geo) use ($req, $email) {
							
							Daemon::log(isset($geo['Placemark'][0]['Point']['coordinates']) ? $geo['Placemark'][0]['Point']['coordinates'] : null);
								$req->appInstance->accounts->saveAccount(array(
									'email' => $email,
									'locationCoords' => isset($geo['Placemark'][0]['Point']['coordinates']) ? $geo['Placemark'][0]['Point']['coordinates'] : null,
								), null, true);
							
							});
							
						}
						$req->appInstance->accounts->getAccountByEmail($email, function ($account) use ($req, $password) {
							if (!$account) {
								$req->setResult(array('success' => false));
								return;
							}
							$req->appInstance->outgoingmail->mailTemplate('mailAccountConfirmation', $account['email'], array(
								'password' => $password
							));
																			
							$req->attrs->session['accountId'] = $account['_id'];
							$req->updatedSession = true;
							$req->setResult(array('success' => true));
						});
					});
				}
				else {
					$req->setResult(array('success' => false, 'errors' => $errors));
				}
				
			});
			$req->job->req = $req;

			$job('captchaPreCheck', function($jobname, $job) {
				$job->req->components->Account->getRecentSignupsCount(function($result) use ($job, $jobname) {
					if ($result['n'] > 0) {
						$job('captcha', CmpCAPTCHA::checkJob());
					}
					$job->setResult($jobname, array());
				});
			});
			
			$job('username', function($jobname, $job) {
			
				$username = Request::getString($job->req->attrs->request['username']);
				if ($username === '') {
					$job->setResult($jobname,array());
					return;
				}
				if (($r = $job->req->components->Account->checkUsernameFormat($username)) !== true) {
					$job->setResult($jobname, array($r));
					return;
				}
				$job->req->appInstance->accounts->getAccountByUnifiedName(
					$username,
					function($account) use ($jobname, $job) {
			 
					$errors = array();
					if ($account) {
						$errors[] = 'Username already taken.';
					}
					
					$job->setResult($jobname, $errors);
				});
			});
			
			
			$job('email', function($jobname, $job) {
				if (filter_var(Request::getString($job->req->attrs->request['email']), FILTER_VALIDATE_EMAIL) === false) {
					$job->setResult($jobname, array('Incorrect E-Mail.'));
					return;
				}
				$job->req->appInstance->accounts->getAccountByEmail(
					Request::getString($job->req->attrs->request['email']),
					function($account) use ($jobname, $job) {
			 
					$errors = array();
					if ($account) {
						$errors[] = 'Another account already registered with this E-Mail.';
					}
					
					$job->setResult($jobname, $errors);
				});
			});
			
			
			$job();
		});
	}
	
	public function checkUsernameFormat($username) {
		if (preg_match('~^(?![\-_\x20])[A-Za-z\d_\-А-Яа-яёЁ\x20]{2,25}(?<![\-_\x20])$~u',$username) == 0) {
			return 'Incorrect username format.';
		}
		elseif (preg_match('~(.)\1\1\1~',$username) > 0) {
			return 'Username contains 4 identical symbols in a row.';
		}
		return true;
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
