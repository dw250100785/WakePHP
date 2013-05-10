<?php
namespace WakePHP\ExternalAuthAgents;

use PHPDaemon\Core\Daemon;
use WakePHP\Core\OAuth;
use WakePHP\Core\Request;

class Twitter extends Generic {
	public function auth() {
		$request_token_url = $this->cmp->config->twitter_auth_url->value . 'oauth/request_token';
		$this->appInstance = $this->req->appInstance;
		$base_url          = ($_SERVER['HTTPS'] === 'off' ? 'http' : 'https') . '://' . $this->appInstance->config->domain->value;
		$redirect_url      = $base_url . '/component/Account/ExternalAuthRedirect/json?agent=Twitter';
		$this->req->header('Cache-Control: no-cache, no-store, must-revalidate');
		$this->req->header('Pragma: no-cache');
		$this->req->header('Expires: Sat, 26 Jul 1997 05:00:00 GMT');
		$this->appInstance->httpclient->post(
			$request_token_url,
			[],
			['headers'  => ['Authorization: ' . $this->getAuthorizationHeader($request_token_url, ['oauth_callback' => $redirect_url])],
			 'resultcb' => function ($conn, $success) use ($request_token_url, $base_url, $redirect_url) {
				 if ($success) {
					 parse_str($conn->body, $response);
					 if ($conn->responseCode > 299) {
						 /** try to fix timestamp difference */
						 if (!empty($conn->headers['HTTP_DATE'])) {
							 $timestamp = strtotime($conn->headers['HTTP_DATE']);
							 $this->appInstance->httpclient->post(
								 $request_token_url, [],
								 ['headers'  => ['Authorization: ' .
										 $this->getAuthorizationHeader($request_token_url, ['oauth_callback' => $redirect_url, 'oauth_timestamp' => $timestamp])],
								  'resultcb' => function ($conn, $success) use ($base_url, $redirect_url) {
									  $status = $conn->responseCode;
									  if ($success && $status > 199 && $status < 300) {
										  parse_str($conn->body, $response);
										  $this->req->header('Location: '
																	 . $this->cmp->config->twitter_auth_url->value
																	 . 'oauth/authenticate/?oauth_token=' . rawurlencode($response['oauth_token']));
									  }
									  else {
										  $this->req->header('Location: ' . $base_url);
									  }
									  $this->req->setResult();
								  }
								 ]);
						 }
						 else {
							 goto err_response;
						 }
					 }
					 elseif (!isset($response['oauth_token']) || !isset($response['oauth_token_secret'])) {
						 $this->req->header('Location: ' . $base_url);
						 $this->req->setResult();
						 return;
					 }
					 else {
						 $request_token_url = $this->cmp->config->twitter_auth_url->value . 'oauth/authenticate/?oauth_token=' . rawurlencode($response['oauth_token']);
						 $this->req->header('Location: ' . $request_token_url);
						 $this->req->setResult();
					 }
				 }
				 else {
					 err_response:
					 $this->req->header('Location: ' . $base_url);
					 $this->req->setResult();
					 return;
				 }
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
			$this->req->setResult();
			return;
		}
		$url      = $this->cmp->config->twitter_auth_url->value . 'oauth/access_token';
		$base_url = ($_SERVER['HTTPS'] === 'off' ? 'http' : 'https') . '://' . $this->appInstance->config->domain->value;
		$this->appInstance->httpclient->post(
			$url,
			['oauth_verifier' => $_GET['oauth_verifier']],
			['headers'  => ['Authorization: ' . $this->getAuthorizationHeader($url, ['oauth_token' => $_GET['oauth_token']])],
			 'resultcb' => function ($conn, $success) use ($base_url) {
				 if ($success) {
					 parse_str($conn->body, $response);
					 $user_twitter_id   = $response['user_id'];
					 $user_twitter_name = $response['screen_name'];
					 $this->req->components->account->acceptUserAuthentication(['twitterId' => $user_twitter_id],
																			   ['twitterName' => $user_twitter_name,
																				'username'    => $user_twitter_name],
						 function () use ($base_url) {
							 $this->req->header('Location: ' . $base_url);
							 $this->req->setResult();
						 });
				 }
			 }
			]
		);
	}
}
