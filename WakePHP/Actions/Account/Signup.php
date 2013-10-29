<?php
namespace WakePHP\Actions\Account;

use WakePHP\Actions\Generic;
use PHPDaemon\Request\Generic as Request;
use PHPDaemon\Core\ComplexJob;
use WakePHP\Components\Captcha;
use WakePHP\Core\Request as WakePHPRequest;

/**
 * Class Signup
 * @package WakePHP\Actions\Account
 * @dynamic_fields
 */
class Signup extends Generic {

	public function perform() {
		$this->req->onSessionStart(function ($sessionEvent) {
			/** @var ComplexJob $job */
			$captchaPostCheck = false;
			$job      = $this->req->job = new ComplexJob(function ($job) use (&$captchaPostCheck) {
				$errors = array();
				foreach ($job->results as $result) {
					if (sizeof($result) > 0) {
						$errors = array_merge_recursive($errors, $result);
					}
				}
				/** @var WakePHPRequest $req */

				if (sizeof($errors) > 0) {
					$this->req->setResult(array('success' => false, 'errors' => $errors));
					return;
				}
				if (!$captchaPostCheck) {
					$captchaPostCheck = true;
					if (isset($job->results['captcha'])) {
						$job('captcha', Captcha::checkJob($this->req, true));
						return;
					}
				}

				$this->req->appInstance->accounts->saveAccount(
					array(
						'email'            => $email = Request::getString($_REQUEST['email']),
						'username'         => Request::getString($_REQUEST['username']),
						'location'         => $location = Request::getString($_REQUEST['location']),
						'password'         => $password = Request::getString($_REQUEST['password']),
						'confirmationcode' => $code = $this->cmp->getConfirmationCode($email),
						'regdate'          => time(),
						'etime'            => time(),
						'ip'               => $_SERVER['REMOTE_ADDR'],
						'subscription'     => 'daily',
						'aclgroups'        => array('Users'),
						'acl'              => array(),
					), function ($lastError) use ($email, $password, $location, $code) {
					if ($location !== '') {

						$this->req->components->GMAPS->geo($location, function ($geo) use ($email) {

							$this->req->appInstance->accounts->saveAccount(array(
								'email'          => $email,
								'locationCoords' => isset($geo['Placemark'][0]['Point']['coordinates']) ? $geo['Placemark'][0]['Point']['coordinates'] : null,
							), null, true);

						});

					}
					$this->req->appInstance->accounts->getAccountByUnifiedEmail($email, function ($account) use ($password, $code) {
						if (!$account) {
							$this->req->setResult(array('success' => false));
							return;
						}
						$this->req->appInstance->Sendmail->mailTemplate('mailAccountConfirmation', $account['email'], array(
							'email'    => $account['email'],
							'password' => $password,
							'code'     => $code,
							'locale'   => $this->req->appInstance->getLocaleName(Request::getString($_REQUEST['LC'])),
						));

						$this->cmp->loginAs($account);
						$this->req->setResult(array('success' => true));
					});
				});

			});
			$job('captchaPreCheck', function ($jobname, $job) {
				/** @var ComplexJob $job */
				$this->req->components->Account->getRecentSignupsCount(function ($result) use ($job, $jobname) {
					/** @var ComplexJob $job */
					if ($result['n'] > -1) {
						$job('captcha', Captcha::checkJob($this->req, false));
					}
					$job->setResult($jobname, []);
				});
			});

			$job('username', function ($jobname, $job) {
				/** @var ComplexJob $job */
				$username = Request::getString($_REQUEST['username']);
				if ($username === '') {
					$job->setResult($jobname, array());
					return;
				}
				if (($r = $this->req->components->Account->checkUsernameFormat($username)) !== true) {
					$job->setResult($jobname, array($r));
					return;
				}
				$this->req->appInstance->accounts->getAccountByUnifiedName(
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
				if (filter_var(Request::getString($_REQUEST['email']), FILTER_VALIDATE_EMAIL) === false) {
					$job->setResult($jobname, array('email' => 'Incorrect E-Mail.'));
					return;
				}
				$this->req->appInstance->accounts->getAccountByUnifiedEmail(
					Request::getString($_REQUEST['email']),
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
}
