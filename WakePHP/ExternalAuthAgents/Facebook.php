<?php
namespace WakePHP\ExternalAuthAgents;

use PHPDaemon\Clients\HTTP\Pool as HTTPClient;
use PHPDaemon\Core\Daemon;
use PHPDaemon\Core\Debug;
use WakePHP\Core\Request;

class Facebook extends Generic {
	public function auth() {
		Daemon::log(Debug::dump($this->cmp->config->facebook_app_key->value));
		Daemon::log(Debug::dump($this->cmp->config->facebook_app_secret->value));
		$this->req->redirectTo(
			['https://www.facebook.com/dialog/oauth/',
				'client_id'     => $this->cmp->config->facebook_app_key->value,
				'response_type' => 'code',
				'scope'         => 'email',
				'redirect_uri'  => $this->getCallbackURL()
			]);
	}

	public function redirect() {
		if (!$this->checkReferer($this->appInstance->config->domain->value)) {
			$this->req->setResult(['error' => 'Wrong referer']);
			return;
		}
		$code = Request::getString($_GET['code']);
		if ($code === '') {
			Daemon::log('Authentication failed');
			$this->req->status(401);
			$this->req->setResult(['error' => 'Authenticaion failed']);
			return;
		}
		$this->appInstance->httpclient->get(
			['https://graph.facebook.com/oauth/access_token',
				'client_id'     => $this->cmp->config->facebook_app_key->value,
				'redirect_uri'  => $this->req->getBaseUrl() . '/component/Account/ExternalAuthRedirect/json?agent=Facebook',
				'client_secret' => $this->cmp->config->facebook_app_secret->value,
				'code'          => $code],
			function ($conn, $success) {
				if (!$success) {
					$this->req->status(400);
					$this->req->setResult(['error' => 'request declined']);
					return;
				}
				parse_str($conn->body, $response);
				if (!isset($response['access_token'])) {
					$this->req->status(403);
					$this->req->setResult(['error' => 'no access_token']);
					return;
				}
				$this->appInstance->httpclient->get(
					['https://graph.facebook.com/me',
						'fields'       => 'id,name,email',
						'format'       => 'json',
						'access_token' => $response['access_token']
					],
					function ($conn, $success) {
						$response = json_decode($conn->body, true);
						$id       = Request::getString($response['id']);
						if (!$success || !is_array($response) || empty($id)) {
							$this->req->status(302);
							$this->req->header('Location: ' . $this->req->getBaseUrl());
							$this->req->setResult(['error' => 'Unrecognized response']);
							return;
						}
						$data = [];
						if (isset($response['name'])) {
							$data['username'] = Request::getString($response['name']);
						}
						if (isset($response['email'])) {
							$data['email'] = Request::getString($response['email']);
						}
						if (isset($_REQUEST['external_token'])) {
							$data['external_token'] = Request::getString($_REQUEST['external_token']);
						}
						$this->req->components->account->acceptUserAuthentication('facebook', $id, $data,
							function () {
								$this->finalRedirect();
							});
					});
			}
		);
	}
}
