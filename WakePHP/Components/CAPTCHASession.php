<?php
namespace WakePHP\Components;

class CAPTCHASession extends SocketSession
{

	const PSTATE_FIRSTLINE = 1;
	const PSTATE_HEADERS   = 2;
	const PSTATE_BODY      = 3;
	public $pstate = 1;
	public $headers = array();
	public $onResponse = array();
	public $contentLength = 0;
	public $body = '';
	public $EOL = "\r\n";

	public function validate($cb)
	{

		if (empty($this->appInstance->req->attrs->request['recaptcha_challenge_field']))
		{
			$cb(false, '');
			return;
		}
		if (empty($this->appInstance->req->attrs->request['recaptcha_response_field']))
		{
			$cb(false, 'incorrect-captcha-sol');
			return;
		}
		if (empty($this->config->privatekey->value))
		{
			$cb(false, 'empty-private-key');
			return;
		}
		$body = http_build_query(array(
			'privatekey' => $this->config->privatekey->value,
			'remoteip'   => $this->appInstance->req->attrs->server['REMOTE_ADDR'],
			'challenge'  => Request::getString($this->appInstance->req->attrs->request['recaptcha_challenge_field']),
			'response'   => Request::getString($this->appInstance->req->attrs->request['recaptcha_response_field']),
		));
		$this->writeln('POST /recaptcha/api/verify HTTP/1.0');
		$this->writeln('Host: www.google.com');
		$this->writeln('Content-type: application/x-www-form-urlencoded');
		$this->writeln('Content-length: '.strlen($body));
		$this->writeln('User-Agent: reCAPTCHA/phpDaemon');
		$this->writeln('');
		$this->write($body);
		$this->onResponse[] = $cb;
	}

	public function writeln($s)
	{
		parent::writeln($s);
	}

	/**
	 * Called when new data received
	 * @param string $buf New data
	 * @return void
	 */
	public function stdin($buf)
	{
		if ($this->pstate===self::PSTATE_BODY)
		{
			goto body;
		}
		$this->buf .= $buf;
		while (($line = $this->gets())!==FALSE)
		{
			if ($line==='')
			{
				$this->body .= $this->buf;
				$this->buf    = '';
				$this->pstate = self::PSTATE_BODY;
				break;
			}
			if ($this->pstate===self::PSTATE_FIRSTLINE)
			{
				$this->headers['STATUS'] = $line;
				$this->pstate            = self::PSTATE_HEADERS;
			}
			else
			{
				$e = explode(': ', $line);

				if (isset($e[1]))
				{
					$this->headers['HTTP_'.strtoupper(strtr($e[0], \HTTPRequest::$htr))] = $e[1];
				}
			}
		}
		if (isset($this->headers['HTTP_CONTENT_LENGTH']))
		{
			$this->contentLength = (int)$this->headers['HTTP_CONTENT_LENGTH'];

		}
		body:
		$this->body .= $this->buf;
		if (strlen($this->body) >= $this->contentLength)
		{
			list($resultP, $text) = explode("\n", $this->body);
			$result = $resultP==='true';

			$f = array_shift($this->onResponse);

			if ($f)
			{
				call_user_func($f, $result, $text);
			}
		}
	}

	/**
	 * Called when session finishes
	 * @return void
	 */
	public function onFinish()
	{
		$this->finished = TRUE;

		unset($this->appInstance->servConn[$this->addr][$this->connId]);
		unset($this->appInstance->sessions[$this->connId]);
	}
}