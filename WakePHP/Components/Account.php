<?php
namespace WakePHP\Components;

use PHPDaemon\Clients\HTTP\Pool as HTTPClient;
use PHPDaemon\Clients\Mongo\Cursor;
use PHPDaemon\Core\ComplexJob;
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
	/**
	 * @return callable
	 */
	public function onAuthEvent() {
		return function ($authEvent) {
			/** @var DeferredEventCmp $authEvent */
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

	/**
	 * @param callable $cb
	 */
	public function getRecentSignupsCount($cb) {
		$this->appInstance->accounts->getRecentSignupsFromIP($_SERVER['REMOTE_ADDR'], $cb);
	}

	public function UsernameAvailablityCheckController() {
		$username = Request::getString($_REQUEST['username']);
		if (($r = $this->checkUsernameFormat($username)) !== true) {
			$this->req->setResult(array('success' => true, 'error' => $r));
			return;
		}
		$this->appInstance->accounts->getAccountByUnifiedName($username, function ($account) {
			if ($account) {
				$this->req->setResult(array('success' => true, 'error' => 'Username already taken.'));
			}
			else {
				$this->req->setResult(array('success' => true));
			}
		});
	}

	public function GenKeccakController() {
		$str    = Request::getString($_REQUEST['str']);
		$size   = Request::getInteger($_REQUEST['size']);
		$rounds = Request::getInteger($_REQUEST['rounds']);
		if (!$rounds) {
			$rounds = 24;
		}
		$salt = '$512=24';
		$hash = \WakePHP\Core\Crypt::hash($str, $salt);
		$hex  = trim(str_replace('\\x', ' ', \PHPDaemon\Core\Debug::exportBytes(base64_decode($hash), true)));
		$this->req->setResult(['stringWithSalt' => $str . $salt, 'base64' => $hash, 'salt' => $salt, 'hex' => $hex, 'rounds' => 24]);
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

	public function SignupController() {
		$this->onSessionStart(function ($sessionEvent) {
			/** @var ComplexJob $job */
			$job      = $this->req->job = new ComplexJob(function ($job) {
				$errors = array();
				foreach ($job->results as $result) {
					if (sizeof($result) > 0) {
						$errors = array_merge_recursive($errors, $result);
					}
				}
				/** @var WakePHPRequest $req */
				$req = $job->req;

				if (sizeof($errors) === 0) {

					$req->appInstance->accounts->saveAccount(
						array(
							'email'            => $email = Request::getString($req->attrs->request['email']),
							'username'         => Request::getString($req->attrs->request['username']),
							'location'         => $location = Request::getString($req->attrs->request['location']),
							'password'         => $password = Request::getString($req->attrs->request['password']),
							'confirmationcode' => $code = $this->getConfirmationCode($email),
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
			$job->req = $this->req;

			$job('captchaPreCheck', function ($jobname, $job) {
				/** @var ComplexJob $job */
				/** @var WakePHPRequest $job->req */
				$job->req->components->Account->getRecentSignupsCount(function ($result) use ($job, $jobname) {
					/** @var ComplexJob $job */
					if ($result['n'] > 0) {
						$job('captcha', CAPTCHA::checkJob());
					}
					$job->setResult($jobname, array());
				});
			});

			$job('username', function ($jobname, $job) {
				/** @var ComplexJob $job */
				/** @var WakePHPRequest $job->req */
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
				/** @var ComplexJob $job */
				/** @var WakePHPRequest $job->req */
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

	public function ExternalAuthController() {
		if (!($AuthAgent = \WakePHP\ExternalAuthAgents\Generic::getAgent(Request::getString($this->req->attrs->get['agent']), $this))) {
			$this->req->setResult(['error' => true, 'errmsg' => 'Unrecognized external auth agent']);
			return;
		}
		if (isset($_GET['backurl'])) {
			$AuthAgent->setBackUrl(Request::getString($_GET['backurl']));
		}
		$AuthAgent->auth();
	}

	/**
	 * @return bool
	 */
	public function checkReferer() {
		if ($this->req->controller === 'ExternalAuthRedirect') {
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
		return $this->req->checkDomainMatch();
	}

	public function ExternalAuthRedirectController() {
		if (!($AuthAgent = \WakePHP\ExternalAuthAgents\Generic::getAgent(Request::getString($this->req->attrs->get['agent']), $this))) {
			$this->req->setResult(['error' => true, 'errmsg' => 'Unrecognized external auth agent']);
			return;
		}
		$AuthAgent->redirect();
	}

	/**
	 * @param $account
	 * @param null $cb
	 */
	public function loginAs($account, $cb = null) {
		$_SESSION['accountId']     = $account['_id'];
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
		$this->onSessionStart(function () use ($ns, $id, $add, $cb) {
			$crd = ['ns' => $ns, 'id' => $id];
			$this->appInstance->accounts->getAccount(['credentials' => ['$elemMatch' => $crd]],
				function ($account) use ($ns, $id, $cb, $crd, $add) {
					if ($account) {
						$this->loginAs($account, $cb);
						return;
					}
					if (!isset($add['email'])) {
						$_SESSION['extAuth']       = $crd;
						$_SESSION['extAuthAdd']    = $add;
						$this->req->updatedSession = true;
						$this->req->header('Location: ' . $this->req->getBaseUrl() . '/' . $this->req->locale . '/account/finishSignup');
						$this->req->setResult([]);
						return;
					}
					$this->appInstance->accounts->getAccountByEmail($add['email'], function ($account) use ($crd, $add, $cb) {
							if ($account) {
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

	public function ExtAuthRequestsListController() {
		$this->onAuth(function () {
			if (!$this->req->account['logged']) {
				$this->req->setResult([]);
				return;
			}
			$user_id = $this->req->account['_id'];
			$limit   = Request::getInteger($_REQUEST['limit']);
			$offset  = Request::getInteger($_REQUEST['offset']);
			if ($limit < 1) {
				$limit = 100;
			}
			if ($offset < 0) {
				$offset = 0;
			}
			$this->appInstance->externalAuthTokens->findWaiting($user_id, $limit, $offset, 'ctime,_id,ip,useragent', function ($cursor) {
				$result = [];
				foreach ($cursor->items as $item) {
					$item['id'] = (string)$item['_id'];
					$result[]   = $item;
				}
				$this->req->setResult($result);
				$cursor->destroy();
			});
		});
	}

	public function ExtAuthManageRequestsController() {
		$this->onAuth(function () {
			if (!$this->req->account['logged']) {
				$this->req->setResult([]);
				return;
			}
			$intToken = Request::getString($_REQUEST['request_id']);
			if ($intToken === '') {
				$this->req->setResult([]);
				return;
			}
			$answer = Request::getString($_REQUEST['answer']);
			if (!in_array($answer, ['yes', 'no', 'not_sure'])) {
				$this->req->setResult([]);
				return;
			}
			$this->appInstance->externalAuthTokens->findByIntToken($intToken, function ($token) use ($answer) {
				Daemon::log(Debug::dump($token));
				if (!$token) {
					$this->req->setResult([]);
					return;
				}
				if ($answer === 'yes') {
					$token['status'] = 'accepted';
				}
				elseif ($answer === 'no') {
					$token['status'] = 'rejected';
				}
				elseif ($answer === 'not_sure') {
					$token['status'] = 'delayed';
				}
				$this->appInstance->externalAuthTokens->save($token, function () {
					$this->req->setResult(['success' => true]);
					return;
				});
			});
		});
	}

	/**
	 *
	 */
	public function finishSignupController() {
		$this->onSessionRead(function () {
			if (!isset($_SESSION['extAuth'])) {
				$this->req->setResult(['success' => false,
									   'errors'  => ['email' => 'Session expired']
									  ]);
				return;
			}
			if (($email = Request::getString($_REQUEST['email'])) === '') {
				$this->req->setResult(['success' => false,
									   'errors'  => ['email' => 'Empty E-Mail']
									  ]);
				return;
			}
			if (!isset($_SESSION['credentials']['email'])) {
				$_SESSION['credentials']['email'] = $email;
				$this->req->updatedSession        = true;
			}

			//send
			$credentials = $_SESSION['extAuth'];
			$this->appInstance->externalSignupRequests->getRequestByCredentials($credentials, function ($request) use ($email, $credentials) {
				if (!$request || !isset($request['code'])) {
					$code = $this->getConfirmationCode($email);
					$this->appInstance->externalSignupRequests->save(['email'       => Encoding::toUTF8($email),
																	  'code'        => Encoding::toUTF8($code),
																	  'credentials' => Encoding::toUTF8($credentials)],
						function ($lastError) use ($email, $code) {
							if (!isset($lastError['ok'])) {
								$this->req->setResult(['success' => false,
													   'errors'  => ['email' => 'Sorry, internal error.']]);
								return;
							}
							$this->req->appInstance->Sendmail->mailTemplate('mailAccountFinishSignup', $email, [
								'email'  => $email,
								'code'   => $code,
								'locale' => $this->req->appInstance->getLocaleName(Request::getString($this->req->attrs->request['LC'])),
							]);
							$this->req->setResult(['success' => true, 'status' => 'sent']);
							return;
						});
				}
				else {
					if ('' === ($user_code = Request::getString($_REQUEST['code']))) {
						$this->req->appInstance->Sendmail->mailTemplate('mailAccountFinishSignup', $email, [
							'email'  => $email,
							'code'   => $request['code'],
							'locale' => $this->req->appInstance->getLocaleName(Request::getString($this->req->attrs->request['LC'])),
						]);
						$this->req->setResult(['success' => true, 'status' => 'sent']);
						return;
					}
					if ($user_code === $request['code']) {
						$account                = $this->appInstance->accounts->getAccountBase($this->req);
						$account['email']       = $email;
						$account['credentials'] = [$credentials];
						$this->appInstance->accounts->saveAccount($account, function ($lastError) use ($email, $request) {
							if (!isset($lastError['ok'])) {
								$this->req->setResult(['success' => false,
													   'errors'  => ['email' => 'Sorry, internal error.']]);
								return;
							}
							$this->appInstance->accounts->getAccountByEmail($email, function ($account) use ($request) {
								if (!$account) {
									$this->req->setResult(['success' => false,
														   'errors'  => ['email' => 'Sorry, internal error.']]);
									return;
								}
								$this->appInstance->externalSignupRequests->remove(['_id' => new \MongoId($request['_id'])]);
								$this->loginAs($account);
								$this->req->setResult(['success' => true, 'status' => 'verified']);
								return;
							});
						});
					}
					else {
						$this->req->setResult(['success' => false, 'errors' => ['code' => 'Wrong code']]);
						return;
					}
				}
			});
		});
	}

	public function ProfileController() {
		$req = $this->req;
		$this->onAuth(function ($result) use ($req) {
			if (!$req->account['logged']) {
				$req->setResult(array('success' => false, 'goLoginPage' => true));
				return;
			}
			$job      = $req->job = new ComplexJob(function ($job) {
				/** @var ComplexJob $job */
				$errors = array();
				foreach ($job->results as $result) {
					if (sizeof($result) > 0) {
						$errors = array_merge_recursive($errors, $result);
					}
				}
				/** @var WakePHPRequest $req */
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
				/** @var ComplexJob $job */
				/** @var WakePHPRequest $job->req */
				/** @var WakePHPRequest $req */
				$req = $job->req;
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

	/**
	 *
	 */
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

				/** @noinspection PhpIllegalArrayKeyTypeInspection */
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
					/** @noinspection PhpIllegalArrayKeyTypeInspection */
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
					/** @var ComplexJob $job */
					$job->setResult($jobname, $result['n']);
				});
			});

			$job('countFiltered', function ($jobname, $job) use ($where, $limit) {
				/** @var ComplexJob $job */
				/** @var WakePHPRequest $job->req */
				$job->req->appInstance->accounts->countAccounts(function ($result) use ($job, $jobname, $where) {
					$job->setResult($jobname, $result['n']);
				}, array(
					   'where' => $where,
				   ));
			});

			$job('find', function ($jobname, $job) use ($where, $sort, $fields, $fieldNames, $field, $offset, $limit) {
				$job->req->appInstance->accounts->findAccounts(function ($cursor) use ($jobname, $job, $fieldNames, $offset, $limit) {
					/** @var Cursor $cursor */
					/** @var ComplexJob $job */
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

	/**
	 *
	 */
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

	/**
	 * @param $password
	 * @return bool|string
	 */
	public function checkPasswordFormat($password) {
		if (strlen($password) < 4) {
			return 'The chosen password is too short.';
		}
		return true;
	}

	/**
	 * @param $username
	 * @return bool|string
	 */
	public function checkUsernameFormat($username) {
		if (preg_match('~^(?![\-_\x20])[A-Za-z\d_\-А-Яа-яёЁ\x20]{2,25}(?<![\-_\x20])$~u', $username) == 0) {
			return 'Incorrect username format.';
		}
		elseif (preg_match('~(.)\1\1\1~', $username) > 0) {
			return 'Username contains 4 identical symbols in a row.';
		}
		return true;
	}

	/**
	 *
	 */
	public function LogoutController() {
		$this->onSessionRead(function ($sessionEvent) {
			unset($this->req->attrs->session['accountId']);
			$this->req->updatedSession = true;
			$this->req->setResult(['success' => true]);
		});
	}

	/**
	 *
	 */
	public function ExtAuthController() {
		$hash = Request::getString($this->req->attrs->request['x']);
		if (!strlen($hash) || base64_decode($hash, true) === false) {
			$this->req->setResult(['success' => false, 'error' => 'Wrong format of extTokenHash']);
			return;
		}
		$this->appInstance->externalAuthTokens->findByExtTokenHash($hash, function ($result) use ($hash) {
			if ($result) {
				$this->req->setResult(['success' => false, 'error' => 'This token was already used.']);
				return;
			}
			$ip       = $this->req->getIp();
			$intToken = \WakePHP\Core\Crypt::hash(Daemon::uniqid() . "\x00" . $ip . "\x00" . \WakePHP\Core\Crypt::randomString());
			$this->appInstance->externalAuthTokens->save([
															 'extTokenHash' => $hash,
															 'intToken'     => $intToken,
															 'ip'           => $ip,
															 'useragent'    => Request::getString($_SERVER['HTTP_USER_AGENT']),
															 'ctime'        => microtime(true),
															 'status'       => 'new'
														 ], function ($lastError) use ($intToken) {
				if (!isset($lastError['n']) || $lastError['n'] === 0) {
					$this->req->setResult(['success' => false, 'errors' => ['code' => 'Sorry, internal error.']]);
					return;
				}
				$type = Request::getString($_REQUEST['type']);
				if ($type === 'email') {
					// send email....
				}
				elseif ($type === 'redirect') {
					$this->req->status(302);
					$this->req->header('Location: ' . HTTPClient::buildUrl(['/' . $this->req->locale . '/account/extauth', 'i' => $intToken]));
				}
				$this->req->setResult(['success' => true, 'intToken' => $intToken]);
			});
		});
	}

	/**
	 *
	 */
	public function ExtAuthPingController() {
		$extToken = Request::getString($this->req->attrs->request['p']);
		if (!strlen($extToken)) {
			$this->req->setResult(['success' => false, 'error' => 'Wrong format of extTokenHash']);
			return;
		}
		$this->appInstance->externalAuthTokens->findByExtToken($extToken, function ($result) {
			if (!$result) {
				$this->req->setResult(['success' => false, 'error' => 'Token not found.']);
				return;
			}
			if ($result['status'] === 'new') {
				$this->req->setResult(['success' => true, 'result' => 'wait']);
				return;
			}
			if ($result['status'] === 'failed') {
				$this->req->setResult(['success' => true, 'result' => 'failed']);
				return;
			}
			if (microtime(true) - $result['ctime'] > 60 * 15) {
				$this->req->setResult(['success' => true, 'result' => 'expired']);
				return;
			}
			$this->appInstance->externalAuthTokens->save([
															 'extTokenHash' => $result['extTokenHash'],
															 'status'       => 'used',
														 ], function ($lastError) use ($result) {
				if (!isset($lastError['n']) || $lastError['n'] === 0) {
					$this->req->setResult(['success' => true, 'result' => 'failed']);
					return;
				}
				$this->onSessionStart(function ($sessionEvent) use ($result) {
					$this->appInstance->accounts->getAccountById($result['uid'], function ($account) {
						$this->loginAs($account);
						$this->req->setResult(['success' => true]);
					});
				});
			});
		});
	}

	/**
	 *
	 */
	public function    AuthenticationController() {

		$this->onSessionStart(function ($sessionEvent) {
			$username = Request::getString($this->req->attrs->request['username']);
			if ($username === '') {
				$this->req->setResult(array('success' => false, 'errors' => array(
					'username' => 'Unrecognized username.'
				)));
				return;
			}
			$this->appInstance->accounts->getAccount(array('$or' => array(
					array('username' => $username),
					array('unifiedemail' => $this->appInstance->accounts->unifyEmail($username))
				))
				, function ($account) {
					if (!$account) {
						$this->req->setResult(array('success' => false, 'errors' => array(
							'username' => 'Unrecognized username.'
						)));
					}
					elseif ($this->appInstance->accounts->checkPassword($account, Request::getString($this->req->attrs->request['password']))) {
						$this->req->attrs->session['accountId'] = $account['_id'];
						$this->req->updatedSession              = true;
						$r                                      = array('success' => true);
						if (isset($account['confirmationcode'])) {
							$r['needConfirm'] = true;
						}
						$this->req->setResult($r);
					}
					else {
						$this->req->setResult(array('success' => false, 'errors' => array(
							'password' => 'Invalid password.'
						)));
					}
				});
		});
	}

	/**
	 *
	 */
	public function    RecoveryController() {

		$this->onSessionStart(function () {

			if (isset($this->req->attrs->request['email'])) {
				$email = Request::getString($this->req->attrs->request['email']);
				$code  = trim(Request::getString($this->req->attrs->request['code']));
				if ($code !== '') {

					$this->appInstance->accountRecoveryRequests->invalidateCode(function ($lastError) use ($email, $code) {
						if ($lastError['n'] > 0) {

							$this->appInstance->accountRecoveryRequests->getCode(function ($result) {
								if (!$result) {
									$this->req->setResult(array('success' => false, 'errors' => array('code' => 'Error happened.')));
									return;
								}

								$this->appInstance->accounts->saveAccount(array(
																			  'email'    => $result['email'],
																			  'password' => $result['password'],
																		  ), function ($lastError) use ($result) {
									if ($lastError['updatedExisting']) {
										$this->req->setResult(array('success' => true, 'status' => 'recovered'));

										$this->appInstance->accounts->confirmAccount(array(
																						 'email' => $result['email'],
																					 ));

									}
									else {
										$this->req->setResult(array('success' => false, 'errors' => array('code' => 'Error happened.')));
									}
								}, true);

							}, $email, $code);

						}
						else {
							$this->req->setResult(array('success' => false, 'errors' => array('code' => 'Incorrect code.')));
						}
					}, $email, $code);
				}
				else {
					$this->appInstance->accounts->getAccountByUnifiedEmail($email, function ($account) use ($email) {
						if (!$account) {
							$this->req->setResult(array('success' => false, 'errors' => array('email' => 'Account not found.')));
							return;
						}
						$this->appInstance->accountRecoveryRequests->getLastCodeByEmail($email, function ($result) use ($email) {

							if (0) { //$result['ts'] + 900 > time()) {
								$this->req->setResult(array('success' => false, 'errors' => array('email' => 'Too often. Wait a bit before next try.')));
							}
							else {
								$password = substr(md5($email . "\x00" . $result['code'] . "\x00" . $this->appInstance->config->cryptsalt->value . "\x00" . mt_rand(0, mt_getrandmax())), mt_rand(0, 26), 6);

								$code = $this->appInstance->accountRecoveryRequests->addRecoveryCode($email, Request::getString($this->req->attrs->server['REMOTE_ADDR']), $password);

								$this->appInstance->Sendmail->mailTemplate('mailAccountAccessRecovery', $email, array(
									'email'    => $email,
									'password' => $password,
									'code'     => $code,
									'locale'   => $this->req->appInstance->getLocaleName(Request::getString($this->req->attrs->request['LC'])),
								));
								$this->req->setResult(array('success' => true, 'status' => 'sent'));
							}

						});
					});
				}
			}
		});
	}

	/**
	 *
	 */
	public function startSession() {
		$session                   = $this->appInstance->sessions->startSession([
																					'ip'        => $this->req->getIp(),
																					'useragent' => Request::getString($_SERVER['HTTP_USER_AGENT']),
																				]);
		$this->req->attrs->session = $session;
		$sid                       = (string)$session['id'];
		$this->req->setcookie('SESSID', $sid, time() + 60 * 60 * 24 * 365, '/', $this->appInstance->config->cookiedomain->value);
	}

	/**
	 * @return callable
	 */
	public function onSessionStartEvent() {
		return function ($sessionStartEvent) {
			/** @var DeferredEventCmp $sessionStartEvent */
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

	/**
	 * @return callable
	 */
	public function onSessionReadEvent() {

		return function ($sessionEvent) {
			/** @var DeferredEventCmp $sessionEvent */
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
