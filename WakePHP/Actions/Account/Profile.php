<?php
namespace WakePHP\Actions\Account;

use WakePHP\Actions\Generic;
use PHPDaemon\Core\ComplexJob;
use PHPDaemon\Request\Generic as Request;

/**
 * Class Profile
 * @package WakePHP\Actions\Account
 * @dynamic_fields
 */
class Profile extends Generic {

	public function perform() {
		if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
			$this->req->setResult(['success' => false, 'err' => 'POST_METHOD_REQUIRED']);

			return;
		}
		$this->cmp->onAuth(function ($result) {
			if (!$this->req->account['logged']) {
				$this->req->setResult(['success' => false, 'goLoginPage' => true]);

				return;
			}
			$job = $this->req->job = new ComplexJob(function ($job) {
				/** @var ComplexJob $job */
				$errors = [];
				foreach ($job->results as $result) {
					if (sizeof($result) > 0) {
						$errors = array_merge_recursive($errors, $result);
					}
				}
				/** @var WakePHPRequest $req */
				if (sizeof($errors) === 0) {

					$update = [
						'email' => $this->req->account['email'],
						'etime' => time(),
					];

					if (isset($_REQUEST['location'])) {
						$update['location'] = $location = trim(Request::getString($_REQUEST['location']));
						if ($update['location'] === '') {
							$update['locationCoords'] = null;
						}
					} else {
						$location = '';
					}

					if (isset($_REQUEST['name'])) {
						$update['name'] = Request::getString($_REQUEST['name']);
					}

					if (isset($_REQUEST['gender'])) {
						$update['gender'] = Request::getString($_REQUEST['gender'], ['', 'm', 'f']);
					}

					if (isset($_REQUEST['birthdate'])) {
						$update['birthdate'] = Request::getString($_REQUEST['birthdate']);
					}

					if (isset($_REQUEST['subscription'])) {
						$update['subscription'] = Request::getString($_REQUEST['subscription'], ['', 'daily', 'thematic']);
					}

					// Language
					if (isset($_REQUEST['language'])) {
						$update['language'] = Request::getString($_REQUEST['language']);
					}

					// Phone
					if (isset($_REQUEST['phone'])) {
						$update['phone'] = Request::getString($_REQUEST['phone']);
					}

					// Session
					if (isset($_REQUEST['autoclose'])) {
						$update['autoclose'] = Request::getString($_REQUEST['autoclose']);
					}

					// Password
					if (($password = Request::getString($_REQUEST['password'])) !== '') {
						$update['password'] = $password;
					}
					$this->req->appInstance->accounts->saveAccount($update, function ($lastError) use ($password, $location) {
						if ($location !== '') {

							$this->req->components->GMAPS->geo($location, function ($geo) {

								$this->appInstance->accounts->saveAccount([
									'email'          => $this->req->account['email'],
									'locationCoords' => isset($geo['Placemark'][0]['Point']['coordinates']) ? $geo['Placemark'][0]['Point']['coordinates'] : null,
								], null, true);

							});

						}
						$this->req->setResult(['success' => true]);
					}, true);
				} else {
					$this->req->setResult(['success' => false, 'errors' => $errors]);
				}

			});

			$job('password', function ($jobname, $job) {
				$errors = [];
				/** @var ComplexJob $job */
				/** @var WakePHPRequest $job->req */
				/** @var WakePHPRequest $req */
				if (($curpassword = Request::getString($_REQUEST['currentpassword'])) !== '') {
					if (!$this->req->appInstance->accounts->checkPassword($this->req->account, $curpassword)) {
						$errors['currentpassword'] = 'Incorrect current password.';
					}
				}
				if (($password = Request::getString($_REQUEST['password'])) !== '') {
					if (Request::getString($_REQUEST['currentpassword']) == '') {
						$errors['currentpassword'] = 'Incorrect current password.';
					}
					if (($r = $this->req->components->Account->checkPasswordFormat($password)) !== true) {
						$errors['password'] = $r;
					}
				}
				$job->setResult($jobname, $errors);
			});

			$job();
		});
	}
}
