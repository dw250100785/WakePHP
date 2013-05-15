<?php
namespace WakePHP\ExternalAuthAgents;

use PHPDaemon\Core\Daemon;
use WakePHP\Core\OAuth;
use WakePHP\Core\Request;

class Twitter extends Generic {
	public function auth() {
		$request_token_url = 'https://api.twitter.com/oauth/request_token';
		$this->req->header('Cache-Control: no-cache, no-store, must-revalidate');
		$this->req->header('Pragma: no-cache');
		$this->req->header('Expires: Sat, 26 Jul 1997 05:00:00 GMT');
		$this->appInstance->httpclient->post(
			$request_token_url,
			[],
			['headers'  => ['Authorization: ' . $this->getAuthorizationHeader(
				$request_token_url,
				['oauth_callback' => $this->req->getBaseUrl() . '/component/Account/ExternalAuthRedirect/json?agent=Twitter'])],
			 'resultcb' => function ($conn, $success) {
				 if (!$success) {
					 $this->req->status(400);
					 $this->req->header('Location: ' . $this->req->getBaseUrl());
					 $this->req->setResult([]);
					 return;
				 }
				 if ($conn->responseCode > 299) {
					 Daemon::log('Wrong timestamp! Twitter authentication was declined.');
					 $this->req->status(400);
					 $this->req->setResult([]);
					 return;
				 }
				 parse_str($conn->body, $response);
				 if (!isset($response['oauth_token']) || !isset($response['oauth_token_secret'])) {
					 $this->req->status(302);
					 $this->req->header('Location: ' . $this->req->getBaseUrl());
					 $this->req->setResult([]);
					 return;
				 }
				 $this->req->status(302);
				 $this->req->header('Location: https://api.twitter.com/oauth/authenticate/?oauth_token=' . rawurlencode($response['oauth_token']));
				 $this->req->setResult([]);
			 }]);
	}

	protected function getAuthorizationHeader($url, $oauth_params = []) {
		$header = 'OAuth ';
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
		$header .= implode(', ', $header_params);
		return $header;
	}

	public function redirect() {
		if (!$this->checkReferer('api.twitter.com')) {
			$this->req->setResult([]);
			return;
		}
		$url = 'https://api.twitter.com/oauth/access_token';
		$this->appInstance->httpclient->post(
			$url,
			['oauth_verifier' => Request::getString($_GET['oauth_verifier'])],
			['headers'  => ['Authorization: ' . $this->getAuthorizationHeader($url, ['oauth_token' => Request::getString($_GET['oauth_token'])])],
			 'resultcb' => function ($conn, $success) {
				 if (!$success) {
					 $this->req->setResult(['error' => 'request declined']);
					 return;
				 }
				 parse_str($conn->body, $response);
				 $user_id = Request::getString($response['user_id']);
				 if ($user_id === '') {
					 $this->req->setResult(['error' => 'no user_id']);
					 return;
				 }
				 $data = [];
				 if (isset($response['screen_name'])) {
					 $data['username'] = Request::getString($response['screen_name']);
				 }

				 $this->req->components->account->acceptUserAuthentication('twitter', $user_id, $data,
					 function () {
						 $this->req->header('Location: ' . $this->req->getBaseUrl());
						 $this->req->setResult([]);
					 });
			 }
			]
		);
	}
}
