<?php
namespace WakePHP\ExternalAuthAgents;

use PHPDaemon\Core\Daemon;
use WakePHP\Core\OAuth;
use WakePHP\Core\Request;

class Facebook extends Generic
{
	public function auth()
	{
		$base_url          = ($_SERVER['HTTPS']==='off' ? 'http' : 'https').'://'.$this->appInstance->config->domain->value;
		$redirect_url      = $base_url.'/component/Account/ExternalAuthRedirect/json?agent=Facebook';
		$request_token_url = $this->cmp->config->facebook_auth_url->value.'?'
				.'client_id='.$this->cmp->config->facebook_app_key->value
				.'&response_type=code'
				.'&scope=email'
				.'&redirect_uri='.rawurlencode($redirect_url);
		$this->appInstance = $this->req->appInstance;
		$this->req->header('Cache-Control: no-cache, no-store, must-revalidate');
		$this->req->header('Pragma: no-cache');
		$this->req->header('Expires: Sat, 26 Jul 1997 05:00:00 GMT');
		$this->req->header('Location: '.$request_token_url);
		$this->req->setResult([]);
	}

	protected function getAuthorizationHeader($url, $oauth_params = [])
	{
		$header = 'OAuth ';
		$params =
				['oauth_consumer_key'     => $this->cmp->config->facebook_app_key->value,
				 'oauth_nonce'            => Daemon::uniqid(),
				 'oauth_signature_method' => 'HMAC-SHA1',
				 'oauth_timestamp'        => time(),
				 'oauth_version'          => '1.0'
				];
		if (!empty($oauth_params))
		{
			$params = array_merge($params, $oauth_params);
		}
		$params['oauth_signature'] = OAuth::getSignature('POST', $url, $params, $this->cmp->config->twitter_app_secret->value);
		$header_params             = [];
		foreach ($params as $param => $value)
		{
			$header_params[] = rawurlencode($param).'="'.rawurlencode($value).'"';
		}
		$header .= implode(', ', $header_params);
		return $header;
	}

	public function redirect()
	{
//		if (!$this->checkReferer('facebook.com')) {
//			$this->req->setResult();
//			return;
//		}
		if (!isset($_GET['code']))
		{
			Daemon::log('Authentication failed');
			$this->req->setResult(['error' => 'Authenticaion failed']);
			return;
		}
		$base_url     = ($_SERVER['HTTPS']==='off' ? 'http' : 'https').'://'.$this->appInstance->config->domain->value;
		$redirect_url = $base_url.'/component/Account/ExternalAuthRedirect/json?agent=Facebook';
		$this->appInstance->httpclient->get(
			[$this->cmp->config->facebook_code_exchange_url->value,
				'client_id'     => $this->cmp->config->facebook_app_key->value,
				'redirect_uri'  => $redirect_url,
				'client_secret' => $this->cmp->config->facebook_app_secret->value,
				'code'          => $_GET['code']],
			['resultcb' => function ($conn, $success) use ($base_url)
			{
				if (!$success)
				{
					$this->req->setResult(['error' => 'request declined']);
					return;
				}
				parse_str($conn->body, $response);
				if (!isset($response['access_token']))
				{
					$this->req->setResult(['error' => 'no access_token']);
					return;
				}
				Daemon::log($_SERVER['HTTP_REFERER']);
				$this->appInstance->httpclient->get(
					[$this->cmp->config->facebook_graph_api->value.'/me',
						'fields'       => 'id,name,email',
						'format'       => 'json',
						'access_token' => $response['access_token']
					],
					['resultcb' => function ($conn, $success) use ($base_url)
					{
						Daemon::log($conn->body);
						$this->req->header('Location: '.$base_url);
						$this->req->setResult();
						return;
					}]);
//				$this->req->components->account->acceptUserAuthentication('facebook', $response['user_id'],
//					['username' => $response['screen_name']],
//					function () use ($base_url)
//					{
//						$this->req->header('Location: '.$base_url);
//						$this->req->setResult();
//					});
			}
			]
		);
	}
}
