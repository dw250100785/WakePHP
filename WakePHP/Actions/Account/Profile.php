<?php
namespace WakePHP\Actions\Account;

use WakePHP\Actions\Generic;
use PHPDaemon\Core\ComplexJob;
use PHPDaemon\Core\Daemon;
use PHPDaemon\Core\Debug;
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

					if (isset($_REQUEST['location'])) {
						$this->req->account['location'] = trim(Request::getString($_REQUEST['location']));
						if ($update['location'] === '') {
							$update['locationCoords'] = null;
						}
					}
					foreach ($_REQUEST as $k => $v) {
						if (!is_string($v)) {
							continue;
						}
						try {
							$this->req->account->setPublicProperty($k, $v);
						} catch (\Exception $e) {
							$errors[$k] = $e->getMessage();
						}
					}
				}

				if (sizeof($errors) === 0) {
					$this->req->account->save(function ($lastError) {
						$this->req->setResult(['success' => true]);
					});
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
					if (!$this->req->account->checkPassword($curpassword)) {
						$errors['currentpassword'] = 'Incorrect current password.';
					}
				} 
				if (Request::getString($_REQUEST['password']) !== '') {
					if (Request::getString($_REQUEST['currentpassword']) == '') {
						$errors['currentpassword'] = 'Incorrect current password.';
					}
				} else {
					unset($_REQUEST['password']);
				}
				$job->setResult($jobname, $errors);
			});

			$job();
		});
	}
}
