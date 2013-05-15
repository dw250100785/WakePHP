<?php
namespace WakePHP\ExternalAuthAgents;

use PHPDaemon\Core\Daemon;
use WakePHP\Core\Request;

class VK extends Generic {
	public function auth() {
		$this->req->status(302);
		$this->req->header('Cache-Control: no-cache, no-store, must-revalidate');
		$this->req->header('Pragma: no-cache');
		$this->req->header('Expires: Sat, 26 Jul 1997 05:00:00 GMT');
		$this->req->header('Location: https://oauth.vk.com/authorize?' .
						   http_build_query(['client_id'     => $this->cmp->config->vk_app_key->value,
											 'response_type' => 'code',
											 'scope'         => 'email',
											 'redirect_uri'  => $this->req->getBaseUrl() . '/component/Account/ExternalAuthRedirect/json?agent=VK']));
		$this->req->setResult([]);
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
			['https://oauth.vk.com/access_token',
				'client_id'     => $this->cmp->config->vk_app_key->value,
				'redirect_uri'  => $this->req->getBaseUrl() . '/component/Account/ExternalAuthRedirect/json?agent=VK',
				'client_secret' => $this->cmp->config->vk_app_secret->value,
				'code'          => $code],
			function ($conn, $success) {
				if (!$success) {
					$this->req->status(400);
					$this->req->setResult(['error' => 'request declined']);
					return;
				}
				parse_str($conn->body, $response);
				$user_id      = Request::getString($response['user_id']);
				$access_token = Request::getString($response['access_token']);
				if ($user_id === '' || $access_token === '') {
					$this->req->status(403);
					$this->req->setResult(['error' => 'no access token or user id']);
					return;
				}
				$this->appInstance->httpclient->get(
					['https://api.vk.com/method/users.get',
						'uids'         => $user_id,
						'fields'       => 'screen_name',
						'access_token' => $access_token
					],
					function ($conn, $success) use ($user_id) {
						$response = json_decode($conn->body, true);
						if (!$success || !is_array($response) || empty($id)) {
							$this->req->status(302);
							$this->req->header('Location: ' . $this->req->getBaseUrl());
							$this->req->setResult(['error' => 'Unrecognized response']);
							return;
						}
						$data = [];
						if (isset($response['screen_name'])) {
							$data['username'] = Request::getString($response['screen_name']);
						}
						$this->req->components->account->acceptUserAuthentication('VK', $user_id, $data,
							function () {
								$this->req->status(302);
								$this->req->header('Location: ' . $this->req->getBaseUrl());
								$this->req->setResult([]);
								return;
							});
					});
			}
		);
	}
}
