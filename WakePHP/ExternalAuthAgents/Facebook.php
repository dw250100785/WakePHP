<?php
namespace WakePHP\ExternalAuthAgents;

use PHPDaemon\Clients\HTTP\Pool as HTTPClient;
use PHPDaemon\Core\Daemon;
use WakePHP\Core\Request;

class Facebook extends Generic {
	public function auth() {
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
				'redirect_uri'  => $this->req->getBaseUrl() . $_SERVER['REQUEST_URI'],
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
					$json_response = json_decode($conn->body, true);
					$err_message   = 'no access_token';
					if (isset($json_response['error']['message'])) {
						$err_message = $json_response['error']['message'];
					}
					$this->req->status(403);
					$this->req->setResult(['error' => $err_message]);
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
							$this->req->redirectTo('/');
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
						$this->req->components->account->acceptUserAuthentication('facebook', $id, $data, function () {
							$this->finalRedirect();
						});
					});
			}
		);
	}
}
