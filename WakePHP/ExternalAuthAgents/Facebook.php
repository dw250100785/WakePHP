<?php
namespace WakePHP\ExternalAuthAgents;

use PHPDaemon\Core\Daemon;
use WakePHP\Core\Request;

class Facebook extends Generic {
	public function auth() {
		$base_url          = ($_SERVER['HTTPS'] === 'off' ? 'http' : 'https') . '://' . $this->appInstance->config->domain->value;
		$redirect_url      = $base_url . '/component/Account/ExternalAuthRedirect/json?agent=Facebook';
		$request_token_url = $this->cmp->config->facebook_auth_url->value . '?'
				. 'client_id=' . rawurlencode($this->cmp->config->facebook_app_key->value)
				. '&response_type=code'
				. '&scope=email'
				. '&redirect_uri=' . rawurlencode($redirect_url);
		$this->req->header('Cache-Control: no-cache, no-store, must-revalidate');
		$this->req->header('Pragma: no-cache');
		$this->req->header('Expires: Sat, 26 Jul 1997 05:00:00 GMT');
		$this->req->header('Location: ' . $request_token_url);
		$this->req->setResult([]);
	}

	public function redirect() {
		if (!$this->checkReferer($this->appInstance->config->domain->value)) {
			$this->req->setResult(['error' => 'Wrong referer']);
			return;
		}
		if (!isset($_GET['code'])) {
			Daemon::log('Authentication failed');
			$this->req->setResult(['error' => 'Authenticaion failed']);
			return;
		}
		$base_url     = ($_SERVER['HTTPS'] === 'off' ? 'http' : 'https') . '://' . $this->appInstance->config->domain->value;
		$redirect_url = $base_url . '/component/Account/ExternalAuthRedirect/json?agent=Facebook';
		$this->appInstance->httpclient->get(
			[$this->cmp->config->facebook_code_exchange_url->value,
				'client_id'     => $this->cmp->config->facebook_app_key->value,
				'redirect_uri'  => $redirect_url,
				'client_secret' => $this->cmp->config->facebook_app_secret->value,
				'code'          => $_GET['code']],
			['resultcb' => function ($conn, $success) use ($base_url) {
				if (!$success) {
					$this->req->setResult(['error' => 'request declined']);
					return;
				}
				parse_str($conn->body, $response);
				if (!isset($response['access_token'])) {
					$this->req->setResult(['error' => 'no access_token']);
					return;
				}
				$this->appInstance->httpclient->get(
					[$this->cmp->config->facebook_graph_api->value . '/me',
						'fields'       => 'id,name,email',
						'format'       => 'json',
						'access_token' => $response['access_token']
					],
					['resultcb' => function ($conn, $success) use ($base_url) {
						if (!$success || !($response = json_decode($conn->body, true)) || !isset($response['id'])) {
							$this->req->status(302);
							$this->req->header('Location: ' . $base_url);
							$this->req->setResult(['error' => 'Unrecognized response']);
							return;
						}
						$data = [];
						if (isset($response['name'])) {
							$data['username'] = $response['name'];
						}
						if (isset($response['email'])) {
							$data['email'] = $response['email'];
						}
						$this->req->components->account->acceptUserAuthentication('facebook', $response['id'], $data,
							function () use ($base_url) {
								$this->req->status(302);
								$this->req->header('Location: ' . $base_url);
								$this->req->setResult();
								return;
							});
					}]);
			}
			]
		);
	}
}
