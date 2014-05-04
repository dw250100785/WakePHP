<?php
namespace WakePHP\ExternalAuthAgents;

use PHPDaemon\Core\Daemon;
use PHPDaemon\Core\Debug;
use WakePHP\Core\OAuth;
use WakePHP\Core\Request;

class Twitter extends Generic {
	public function auth() {
		$request_token_url = 'https://api.twitter.com/oauth/request_token';
		$this->appInstance->httpclient->post(
			$request_token_url,
			[],
			['headers'  => ['Authorization: ' . $this->getAuthorizationHeader(
				$request_token_url,
				['oauth_callback' => $this->getCallbackURL()])],
			 'resultcb' => function ($conn, $success) {
				 if (!$success) {
					 $this->req->redirectTo($this->req->getBaseUrl());
					 return;
				 }
				 if ($conn->responseCode > 299) {
					 $this->req->redirectTo($this->req->getBaseUrl());
					 return;
				 }
				 parse_str($conn->body, $response);
				 if (!isset($response['oauth_token']) || !isset($response['oauth_token_secret'])) {
					 $this->req->redirectTo($this->req->getBaseUrl());
					 return;
				 }
				 $this->req->redirectTo(['https://api.twitter.com/oauth/authenticate/', 'oauth_token' => $response['oauth_token']]);
			 }]);
	}

	protected function getAuthorizationHeader($url, $oauth_params = []) {
		$params =
				['oauth_consumer_key'     => $this->cmp->config->twitter_app_key->value,
				 'oauth_nonce'            => Daemon::uniqid(),
				 'oauth_signature_method' => 'HMAC-SHA1',
				 'oauth_timestamp'        => time(),
				 'oauth_version'          => '1.0'
				];
		if (!empty($oauth_params)) {
			$params = array_merge($params, $oauth_params);
		}
		$params['oauth_signature'] = OAuth::getSignature('POST', $url, $params, $this->cmp->config->twitter_app_secret->value);
		$header_params             = [];
		foreach ($params as $param => $value) {
			$header_params[] = rawurlencode($param) . '="' . rawurlencode($value) . '"';
		}
		$header = 'OAuth ' . implode(', ', $header_params);
		return $header;
	}

	public function redirect() {
		if (!$this->checkReferer('api.twitter.com')) {
			$this->req->status(400);
			$this->req->setResult([]);
			return;
		}
		$url = 'https://api.twitter.com/oauth/access_token';
		$this->appInstance->httpclient->post($url,	['oauth_verifier' => Request::getString($_GET['oauth_verifier'])],
			['headers'  => ['Authorization: ' . $this->getAuthorizationHeader($url, ['oauth_token' => Request::getString($_GET['oauth_token'])])],
			'resultcb' => function ($conn, $success) {
				if (!$success) {
					$this->req->status(403);
					$this->req->setResult(['error' => 'request declined']);
					return;
				}
				parse_str($conn->body, $response);
				$user_id = Request::getString($response['user_id']);
				if ($user_id === '') {
					$this->req->status(400);
					$this->req->setResult(['error' => 'no user_id']);
					return;
				}
				$data = [];
				if (isset($response['screen_name'])) {
					$data['name'] = Request::getString($response['screen_name']);
				}
				$this->req->components->account->acceptUserAuthentication('twitter', $user_id, $data, function () {
					$this->finalRedirect();
				});
			 }
		]);
	}
}
