<?php

/**
 * Account component
 */
class CmpAccount extends Component {

	/**
	 * @return callable
	 */
	public function onAuthEvent() {
		return function ($authEvent) {
			/** @var CmpDeferredEvent $authEvent */
			$authEvent->component->onSessionRead(function ($sessionEvent) use ($authEvent) {
				if (isset($authEvent->component->req->account)) {
					$authEvent->setResult();
					return;
				}
				$cb = function ($account) use ($authEvent) {
					if ($account) {
						$account['logged'] = $account['username'] !== 'Guest';
					}
					$authEvent->component->req->account = $account;
					$authEvent->component->req->propertyUpdated('account');
					$authEvent->setResult();
				};
				if (isset($authEvent->component->req->attrs->session['accountId'])) {
					$authEvent->component->appInstance->accounts->getAccountById($authEvent->component->req->attrs->session['accountId'], function ($account) use ($authEvent, $cb) {
						if (!$account) {
							$authEvent->component->appInstance->accounts->getAccountByName('Guest', $cb);
							return;
						}
						$cb($account);
					});
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
		$req      = $this->req;
		$username = Request::getString($req->attrs->request['username']);
		if (($r = $this->checkUsernameFormat($username)) !== true) {
			$req->setResult(array('success' => true, 'error' => $r));
			return;
		}
		$this->appInstance->accounts->getAccountByUnifiedName($username, function ($account) use ($req) {
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
		$this->onSessionStart(function ($sessionEvent) use ($req) {
			$job      = $req->job = new ComplexJob(function ($job) {
				$errors = array();
				foreach ($job->results as $result) {
					if (sizeof($result) > 0) {
						$errors = array_merge_recursive($errors, $result);
					}
				}
				$req = $job->req;
				if (sizeof($errors) === 0) {

					$req->appInstance->accounts->saveAccount(
						array(
							'email'            => $email = Request::getString($req->attrs->request['email']),
							'username'         => Request::getString($req->attrs->request['username']),
							'location'         => $location = Request::getString($req->attrs->request['location']),
							'password'         => $password = Request::getString($req->attrs->request['password']),
							'confirmationcode' => $code = substr(md5($req->attrs->request['email'] . "\x00"
																			 . $req->appInstance->config->cryptsalt->value . "\x00"
																			 . microtime(true) . "\x00"
																			 . mt_rand(0, mt_getrandmax()))
								, 0, 6),
							'regdate'          => time(),
							'etime'            => time(),
							'ip'               => $req->attrs->server['REMOTE_ADDR'],
							'subscription'     => 'daily',
							'aclgroups'        => array('Users'),
							'acl'              => array(),
						), function ($lastError) use ($req, $email, $password, $location, $code) {
						if ($location !== '') {

							$req->components->GMAPS->geo($location, function ($geo) use ($req, $email) {

								$req->appInstance->accounts->saveAccount(array(
																			 'email'          => $email,
																			 'locationCoords' => isset($geo['Placemark'][0]['Point']['coordinates']) ? $geo['Placemark'][0]['Point']['coordinates'] : null,
																		 ), null, true);

							});

						}
						$req->appInstance->accounts->getAccountByUnifiedEmail($email, function ($account) use ($req, $password, $code) {
							if (!$account) {
								$req->setResult(array('success' => false));
								return;
							}
							$req->appInstance->Sendmail->mailTemplate('mailAccountConfirmation', $account['email'], array(
								'email'    => $account['email'],
								'password' => $password,
								'code'     => $code,
								'locale'   => $req->appInstance->getLocaleName(Request::getString($req->attrs->request['LC'])),
							));

							$req->attrs->session['accountId'] = $account['_id'];
							$req->updatedSession              = true;
							$req->setResult(array('success' => true));
						});
					});
				}
				else {
					$req->setResult(array('success' => false, 'errors' => $errors));
				}

			});
			$job->req = $req;

			$job('captchaPreCheck', function ($jobname, $job) {
				$job->req->components->Account->getRecentSignupsCount(function ($result) use ($job, $jobname) {
					if ($result['n'] > 0) {
						$job('captcha', CmpCAPTCHA::checkJob());
					}
					$job->setResult($jobname, array());
				});
			});

			$job('username', function ($jobname, $job) {

				$username = Request::getString($job->req->attrs->request['username']);
				if ($username === '') {
					$job->setResult($jobname, array());
					return;
				}
				if (($r = $job->req->components->Account->checkUsernameFormat($username)) !== true) {
					$job->setResult($jobname, array($r));
					return;
				}
				$job->req->appInstance->accounts->getAccountByUnifiedName(
					$username,
					function ($account) use ($jobname, $job) {

						$errors = array();
						if ($account) {
							$errors['username'] = 'Username already taken.';
						}

						$job->setResult($jobname, $errors);
					});
			});

			$job('email', function ($jobname, $job) {
				if (filter_var(Request::getString($job->req->attrs->request['email']), FILTER_VALIDATE_EMAIL) === false) {
					$job->setResult($jobname, array('email' => 'Incorrect E-Mail.'));
					return;
				}
				$job->req->appInstance->accounts->getAccountByUnifiedEmail(
					Request::getString($job->req->attrs->request['email']),
					function ($account) use ($jobname, $job) {

						$errors = array();
						if ($account) {
							$errors['email'] = 'Another account already registered with this E-Mail.';
						}

						$job->setResult($jobname, $errors);
					});
			});

			$job();
		});
	}

	public function TwitterAuthController() {
		$url          = $this->config->twitter_auth_url->value . 'oauth/request_token';
		$base_url     = $_SERVER['SERVER_PROTOCOL'] . '://' . $this->appInstance->config->domain->value;
		$redirect_url = $base_url . '/component/Account/TwitterAuthRedirect/json';
		$this->appInstance->httpclient->post(
			$url,
			[],
			['headers'  => ['Authorization: ' . $this->getTwitterAuthorizationHeader($url, ['oauth_callback' => $redirect_url])],
			 'resultcb' => function ($conn, $success) use ($base_url) {
				 if ($success) {
					 parse_str($conn->body, $response);
					 $oauth_token        = $response['oauth_token'];
					 $oauth_token_secret = $response['oauth_token_secret'];
					 /** @var AuthTokensORM $this->appInstance->authtokens */
					 $this->appInstance->authtokens->addToken($oauth_token, $oauth_token_secret, function () use ($oauth_token) {
						 $url = $this->config->twitter_auth_url->value . 'oauth/authenticate/?oauth_token=' . rawurlencode($oauth_token);
						 $this->req->header('Location: ' . $url);
					 });
				 }
				 else {
					 $this->req->header('Location: ' . $base_url);
				 }
				 $this->req->setResult();
			 }]);
	}

	public function TwitterAuthRedirectController() {
		$url      = $this->config->twitter_auth_url->value . 'oauth/access_token';
		$base_url = $_SERVER['SERVER_PROTOCOL'] . '://' . $this->appInstance->config->domain->value;
		$this->appInstance->httpclient->post(
			$url,
			['oauth_verifier' => $_GET['oauth_verifier']],
			['headers'  => ['Authorization: ' . $this->getTwitterAuthorizationHeader($url, ['oauth_token' => $_GET['oauth_token']])],
			 'resultcb' => function ($conn, $success) use ($base_url) {
				 if ($success) {
					 parse_str($conn->body, $response);
					 $user_twitter_id   = $response['user_id'];
					 $user_twitter_name = $response['screen_name'];
					 $this->acceptUserAuthentication(['twitterId' => $user_twitter_id],
													 ['twitterName' => $user_twitter_name]);
				 }
				 else {
					 $this->req->header('Location: ' . $base_url);
				 }
				 $this->req->setResult();
			 }
			]
		);
	}

	protected function acceptUserAuthentication($credentials, $user_data) {
		$this->onSessionStart(function () use ($credentials, $user_data) {
			$this->appInstance->accounts->getAccount($credentials,
				function ($account) use ($credentials, $user_data) {
					$cb = function ($account) {
						$this->req->attrs->session['accountId'] = $account['_id'];
						$this->req->updatedSession              = true;
					};
					if (!$account) {
						$account = array_merge($credentials, $user_data);
						$this->appInstance->accounts->saveAccount($account, function () use ($cb, $credentials) {
							$this->appInstance->accounts->getAccount($credentials, $cb);
						});
					}
					else {
						$cb($account);
					}
				});
		});
	}

	protected function getTwitterAuthorizationHeader($url, $oauth_params = array()) {
		$header = 'OAuth ';
		$params =
				['oauth_consumer_key'     => $this->config->twitter_app_key->value,
				 'oauth_nonce'            => md5(uniqid(rand(), true)),
				 'oauth_signature_method' => 'HMAC-SHA1',
				 'oauth_timestamp'        => time(),
				 'oauth_version'          => '1.0'
				];
		if (!empty($oauth_params)) {
			$params = array_merge($params, $oauth_params);
		}
		$params['oauth_signature'] = $this->getOauthSignature('POST', $url, $params, $this->config->twitter_app_secret->value);
		$header_params             = [];
		foreach ($params as $param => $value) {
			$header_params[] = rawurlencode($param) . '="' . rawurlencode($value) . '"';
		}
		$header .= implode(', ', $header_params);
		return $header;
	}

	protected function getOauthSignature($method, $url, $oauth_params, $app_secret, $user_token = '', $request_params = array()) {
		$method = strtoupper($method);
		$params = array_merge($request_params, $oauth_params);
		ksort($params);
		$signature_base   = $method . '&' . rawurlencode($url) . '&';
		$signature_params = [];
		foreach ($params as $param => $value) {
			$signature_params[] = rawurlencode($param) . '=' . rawurlencode($value);
		}
		$signature_base .= rawurlencode(implode('&', $signature_params));
		$signing_key = rawurlencode($app_secret) . '&' . ($user_token ? rawurlencode($user_token) : '');
		$signature   = str_replace(['+', '%7E'], ['%20', '~'], base64_encode(
			hash_hmac('sha1', $signature_base, $signing_key, true)));
		return $signature;
	}

	public function ProfileController() {
		$req = $this->req;
		$this->onAuth(function ($result) use ($req) {
			if (!$req->account['logged']) {
				$req->setResult(array('success' => false, 'goLoginPage' => true));
				return;
			}
			$job      = $req->job = new ComplexJob(function ($job) {
				$errors = array();
				foreach ($job->results as $result) {
					if (sizeof($result) > 0) {
						$errors = array_merge_recursive($errors, $result);
					}
				}
				$req = $job->req;
				if (sizeof($errors) === 0) {

					$update = array(
						'email'        => $req->account['email'],
						'location'     => $location = Request::getString($req->attrs->request['location']),
						'firstname'    => Request::getString($req->attrs->request['firstname']),
						'lastname'     => Request::getString($req->attrs->request['lastname']),
						'gender'       => Request::getString($req->attrs->request['gender'], array('', 'm', 'f')),
						'birthdate'    => Request::getString($req->attrs->request['birthdate']),
						'subscription' => Request::getString($req->attrs->request['subscription'], array('', 'daily', 'thematic')),
						'etime'        => time(),
					);
					if (($password = Request::getString($req->attrs->request['password'])) !== '') {
						$update['password'] = $password;
					}
					$req->appInstance->accounts->saveAccount($update, function ($lastError) use ($req, $password, $location) {
						if ($location !== '') {

							$req->components->GMAPS->geo($location, function ($geo) use ($req) {

								$req->appInstance->accounts->saveAccount(array(
																			 'email'          => $req->account['email'],
																			 'locationCoords' => isset($geo['Placemark'][0]['Point']['coordinates']) ? $geo['Placemark'][0]['Point']['coordinates'] : null,
																		 ), null, true);

							});

						}
						$req->setResult(array('success' => true));
					}, true);
				}
				else {
					$req->setResult(array('success' => false, 'errors' => $errors));
				}

			});
			$job->req = $req;

			$job('password', function ($jobname, $job) {
				$errors = array();
				$req    = $job->req;
				if (($curpassword = Request::getString($req->attrs->request['currentpassword'])) !== '') {
					if (!$req->appInstance->accounts->checkPassword($job->req->account, $curpassword)) {
						$errors['currentpassword'] = 'Incorrect current password.';
					}
				}
				if (($password = Request::getString($req->attrs->request['password'])) !== '') {
					if (Request::getString($req->attrs->request['currentpassword']) == '') {
						$errors['currentpassword'] = 'Incorrect current password.';
					}
					if (($r = $req->components->Account->checkPasswordFormat($password)) !== true) {
						$errors['password'] = $r;
					}
				}
				$job->setResult($jobname, $errors);
			});

			$job();
		});
	}

	public function ManageAccountsController() {
		$req = $this->req;
		$this->onAuth(function ($result) use ($req) {
			if (!in_array('Superusers', $req->account['aclgroups'], true)) {
				$req->setResult(array('success' => false, 'goLoginPage' => true));
				return;
			}

			static $fields = array(
				'email'     => 1,
				'username'  => 1,
				'regdate'   => 1,
				'ip'        => 1,
				'firstname' => 1,
				'lastname'  => 1,
				'location'  => 1,
				'aclgroups' => 1,
				'_id'       => 1,
			);
			$fieldNames = array_keys($fields);
			$field      = function ($n) use ($fieldNames) {
				if (!isset($fieldNames[$n])) {
					return null;
				}
				return $fieldNames[$n];
			};

			$action = Request::getString($req->attrs->request['action']);
			if ($action === 'EditColumn') {
				$column = $field(Request::getInteger($req->attrs->request['column']));
				if ($column === null) {
					$req->setResult(array('success' => false, 'error' => 'Column not found.'));
					return;
				}

				$req->appInstance->accounts->saveAccount(array(
															 '_id'   => Request::getString($req->attrs->request['id']),
															 $column => $value = Request::getString($req->attrs->request['value'])
														 ), function ($lastError) use ($req, $value) {
					if ($lastError['updatedExisting']) {
						$req->setResult(array('success' => true, 'value' => $value));
					}
					else {
						$req->setResult(array('success' => false, 'error' => 'Account not found.'));
					}
				}, true);

				return;
			}

			$where   = array();
			$sort    = array();
			$sortDir = array();

			foreach ($req->attrs->request as $k => $value) {
				list ($type, $index) = explode('_', $k . '_');
				if ($type === 'iSortCol') {
					$sort[$field($value)] = Request::getString($req->attrs->request['sSortDir_' . $index]) == 'asc' ? 1 : -1;
				}
			}
			unset($sort[null]);

			$offset = Request::getInteger($req->attrs->request['iDisplayStart']);
			$limit  = Request::getInteger($req->attrs->request['iDisplayLength']);

			$job = $req->job = new ComplexJob(function ($job) {

				$job->req->setResult(array(
										 'success'              => true,
										 'sEcho'                => (int)Request::getString($job->req->attrs->request['sEcho']),
										 'iTotalRecords'        => $job->results['countTotal'],
										 'iTotalDisplayRecords' => $job->results['countFiltered'],
										 'aaData'               => $job->results['find'],
									 ));

			});

			$job('countTotal', function ($jobname, $job) {
				$job->req->appInstance->accounts->countAccounts(function ($result) use ($job, $jobname) {
					$job->setResult($jobname, $result['n']);
				});
			});

			$job('countFiltered', function ($jobname, $job) use ($where, $limit) {
				$job->req->appInstance->accounts->countAccounts(function ($result) use ($job, $jobname, $where) {
					$job->setResult($jobname, $result['n']);
				}, array(
					   'where' => $where,
				   ));
			});

			$job('find', function ($jobname, $job) use ($where, $sort, $fields, $fieldNames, $field, $offset, $limit) {
				$job->req->appInstance->accounts->findAccounts(function ($cursor) use ($jobname, $job, $fieldNames, $offset, $limit) {

					$accounts = array();
					foreach ($cursor->items as $item) {
						$account = array();
						foreach ($fieldNames as $k) {
							if (!isset($item[$k])) {
								$val = null;
							}
							else {
								$val = $item[$k];
								if ($k === 'regdate') {
									$val = $val != 0 ? date('r', $val) : '';
								}
								elseif ($k === '_id') {
									$val = (string)$val;
								}
								else {
									if ($k === 'aclgroups') {
										$val = (string)implode(', ', $val);
									}
									$val = htmlspecialchars($val);
								}
							}
							$account[] = $val;
						}
						$accounts[] = $account;
					}
					$cursor->destroy();
					$job->setResult($jobname, $accounts);
				}, array(
					   'fields' => $fields,
					   'sort'   => $sort,
					   'offset' => $offset,
					   'limit'  => -abs($limit),
				   ));

			});

			$job->req = $req;

			$job();

		});
	}

	public function ManageAccountsDeleteController() {
		$req = $this->req;
		$this->onAuth(function ($result) use ($req) {
			if (!in_array('Superusers', $req->account['aclgroups'], true)) {
				$req->setResult(array('success' => false, 'goLoginPage' => true));
				return;
			}
			$req->appInstance->accounts->deleteAccount(array('_id' => Request::getString($req->attrs->request['id'])), function ($lastError) use ($req) {

				if ($lastError['n'] > 0) {
					$req->setResult(array(
										'success' => true,
									));
				}
				else {
					$req->setResult(array(
										'success' => false,
										'error'   => 'Account not found.'
									));
				}
			});

		});
	}

	public function checkPasswordFormat($password) {
		if (strlen($password) < 4) {
			return 'The chosen password is too short.';
		}
		return true;
	}

	public function checkUsernameFormat($username) {
		if (preg_match('~^(?![\-_\x20])[A-Za-z\d_\-А-Яа-яёЁ\x20]{2,25}(?<![\-_\x20])$~u', $username) == 0) {
			return 'Incorrect username format.';
		}
		elseif (preg_match('~(.)\1\1\1~', $username) > 0) {
			return 'Username contains 4 identical symbols in a row.';
		}
		return true;
	}

	public function LogoutController() {
		$req = $this->req;
		$this->onSessionRead(function ($sessionEvent) use ($req) {
			unset($req->attrs->session['accountId']);
			$req->updatedSession = true;
			$req->setResult(array('success' => true));
		});
	}

	public function    AuthenticationController() {

		$req = $this->req;
		$this->onSessionStart(function ($sessionEvent) use ($req) {
			$username = Request::getString($req->attrs->request['username']);
			if ($username === '') {
				$req->setResult(array('success' => false, 'errors' => array(
					'username' => 'Unrecognized username.'
				)));
				return;
			}
			$req->appInstance->accounts->getAccount(array('$or' => array(
					array('username' => $username),
					array('unifiedemail' => $req->appInstance->accounts->unifyEmail($username))
				))
				, function ($account) use ($req) {
					if (!$account) {
						$req->setResult(array('success' => false, 'errors' => array(
							'username' => 'Unrecognized username.'
						)));
					}
					elseif ($req->appInstance->accounts->checkPassword($account, Request::getString($req->attrs->request['password']))) {
						$req->attrs->session['accountId'] = $account['_id'];
						$req->updatedSession              = true;
						$r                                = array('success' => true);
						if (isset($account['confirmationcode'])) {
							$r['needConfirm'] = true;
						}
						$req->setResult($r);
					}
					else {
						$req->setResult(array('success' => false, 'errors' => array(
							'password' => 'Invalid password.'
						)));
					}
				});
		});
	}

	public function    RecoveryController() {

		$req = $this->req;
		$this->onSessionStart(function ($authEvent) use ($req) {

			if (isset($req->attrs->request['email'])) {
				$email = Request::getString($req->attrs->request['email']);
				$code  = trim(Request::getString($req->attrs->request['code']));
				if ($code !== '') {

					$req->appInstance->accountRecoveryRequests->invalidateCode(function ($lastError) use ($req, $email, $code) {
						if ($lastError['n'] > 0) {

							$req->appInstance->accountRecoveryRequests->getCode(function ($result) use ($req) {
								if (!$result) {
									$req->setResult(array('success' => false, 'errors' => array('code' => 'Error happened.')));
									return;
								}

								$req->appInstance->accounts->saveAccount(array(
																			 'email'    => $result['email'],
																			 'password' => $result['password'],
																		 ), function ($lastError) use ($req, $result) {
									if ($lastError['updatedExisting']) {
										$req->setResult(array('success' => true, 'status' => 'recovered'));

										$req->appInstance->accounts->confirmAccount(array(
																						'email' => $result['email'],
																					));

									}
									else {
										$req->setResult(array('success' => false, 'errors' => array('code' => 'Error happened.')));
									}
								}, true);

							}, $email, $code);

						}
						else {
							$req->setResult(array('success' => false, 'errors' => array('code' => 'Incorrect code.')));
						}
					}, $email, $code);
				}
				else {
					$req->appInstance->accounts->getAccountByUnifiedEmail($email, function ($account) use ($req, $email) {
						if (!$account) {
							$req->setResult(array('success' => false, 'errors' => array('email' => 'Account not found.')));
							return;
						}
						$req->appInstance->accountRecoveryRequests->getLastCodeByEmail($email, function ($result) use ($req, $email) {

							if (0) { //$result['ts'] + 900 > time()) {
								$req->setResult(array('success' => false, 'errors' => array('email' => 'Too often. Wait a bit before next try.')));
							}
							else {
								$password = substr(md5($email . "\x00" . $result['code'] . "\x00" . $req->appInstance->config->cryptsalt->value . "\x00" . mt_rand(0, mt_getrandmax())), mt_rand(0, 26), 6);

								$code = $req->appInstance->accountRecoveryRequests->addRecoveryCode($email, Request::getString($req->attrs->server['REMOTE_ADDR']), $password);

								$req->appInstance->Sendmail->mailTemplate('mailAccountAccessRecovery', $email, array(
									'email'    => $email,
									'password' => $password,
									'code'     => $code,
									'locale'   => $req->appInstance->getLocaleName(Request::getString($req->attrs->request['LC'])),
								));
								$req->setResult(array('success' => true, 'status' => 'sent'));
							}

						});
					});
				}
			}
		});
	}

	public function startSession() {
		$session                   = $this->appInstance->sessions->startSession();
		$this->req->attrs->session = $session;
		$sid                       = (string)$session['_id'];
		$this->req->setcookie('SESSID', $sid, time() + 60 * 60 * 24 * 365, '/', $this->appInstance->config->cookiedomain->value);
	}

	public function onSessionStartEvent() {

		return function ($sessionStartEvent) {
			$req = $sessionStartEvent->component->req;
			if (isset($req->session['_id'])) {
				$sessionStartEvent->setResult();
				return;
			}
			$sid = Request::getString($req->attrs->request['SESSID']);
			if ($sid === '') {
				$sessionStartEvent->component->startSession();
				$sessionStartEvent->setResult();
				return;
			}

			$sessionStartEvent->component->onSessionRead(function ($session) use ($sessionStartEvent) {
				if (!$session) {
					$sessionStartEvent->component->startSession();
				}
				$sessionStartEvent->setResult();
			});
		};
	}

	public function onSessionReadEvent() {

		return function ($sessionEvent) {
			$req = $sessionEvent->component->req;
			$sid = Request::getString($req->attrs->cookie['SESSID']);
			if ($sid === '') {
				$sessionEvent->setResult();
				return;
			}
			if ($req->attrs->session) {
				$sessionEvent->setResult();
				return;
			}
			$sessionEvent->component->appInstance->sessions->getSessionById($sid, function ($session) use ($sessionEvent) {
				if ($session) {
					$sessionEvent->component->req->attrs->session = $session;
				}
				$sessionEvent->setResult();
			});
		};
	}
}
