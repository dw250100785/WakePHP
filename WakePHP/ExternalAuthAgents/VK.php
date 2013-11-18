<?php
namespace WakePHP\ExternalAuthAgents;

use PHPDaemon\Core\Daemon;
use PHPDaemon\Core\Debug;
use PHPDaemon\Clients\HTTP\Pool as HTTPClient;
use WakePHP\Core\Request;

class VK extends Generic {
	public function auth() {
		$this->req->redirectTo(
			['https://oauth.vk.com/authorize',
				'client_id'     => $this->cmp->config->vk_app_key->value,
				'response_type' => 'code',
				'scope'         => 'email',
				'redirect_uri'  => $this->getCallbackURL()]);
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
			$get = ['https://api.vk.com/oauth/access_token',
				'client_id'     => $this->cmp->config->vk_app_key->value,
				'redirect_uri'  => HTTPClient::buildUrl([
					$this->req->getBaseUrl() . '/component/Account/ExternalAuthRedirect/json',
					'agent' => 'VK',
					'backurl' => $this->getBackurl(true)
				]),
				'client_secret' => $this->cmp->config->vk_app_secret->value,
				'code'          => $code],
			function ($conn, $success) use (&$get) {
				if (!$success) {
					$this->req->status(400);
					$this->req->setResult(['error' => 'request declined']);
					return;
				}
				Daemon::log(Debug::dump($get));
				Daemon::log(Debug::dump($conn->body));
				$response     = json_decode(rtrim($conn->body), true);
				$user_id      = isset($response['user_id']) ? (int)$response['user_id'] : 0;
				$access_token = Request::getString($response['access_token']);
				if ($user_id === 0 || $access_token === '') {
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
						if (!$success || !is_array($response) || empty($user_id)) {
							$this->req->redirectTo($this->req->getBaseUrl(), false);
							$this->req->setResult(['error' => 'Unrecognized response']);
							return;
						}
						$data = [];
						if (isset($response['screen_name'])) {
							$data['username'] = Request::getString($response['screen_name']);
						}
						$this->req->components->account->acceptUserAuthentication('VK', $user_id, $data, [$this, 'finalRedirect']);
					});
			}
		);
	}
}
