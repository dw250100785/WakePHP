<?php

/**
 * CAPTCHA component
 */
class CmpCAPTCHA extends AsyncServer {

	public $req;
	public $appInstance;
	public function __construct($req) {
		$this->req = $req;
		$this->appInstance = $req->appInstance;
	}	
	/**
	 * Establishes connection
	 * @param string Address
	 * @return integer Connection's ID
	 */
	public function getConnection($addr) {
		if (isset($this->servConn[$addr])) {
			foreach ($this->servConn[$addr] as &$c) {
				return $c;
			}
		} else {
			$this->servConn[$addr] = array();
		}

		$e = explode(':', $addr);

		if (!isset($e[1])) {
			$e[1] = 80;
		}
		$connId = $this->connectTo($e[0], (int) $e[1]);

		$this->sessions[$connId] = new	CmpCAPTCHASession($connId, $this);
		$this->sessions[$connId]->addr = $addr;
		$this->servConn[$addr][$connId] = $connId;

		return $connId;
	}
	public function validate($cb) {
		
		$connId = $this->getConnection('www.google.com');
		$this->sessions[$connId]->validate($cb);
		
	}
}

class CmpCAPTCHASession extends SocketSession {

	const PSTATE_FIRSTLINE = 1;
	const PSTATE_HEADERS = 2;
	const PSTATE_BODY = 3;
	public $pstate = 1;
	public $headers = array();
	public $onResponse = array();
	public $contentLength = 0;
	public $body = '';
	public $EOL = "\r\n";
	public function validate($cb) {
		$body =  http_build_query(array(
			'privatekey' => $this->appInstance->req->appInstance->config->captchaprivatekey->value,
			'remoteip' => $this->appInstance->req->attrs->server['REMOTE_ADDR'],
			'challenge' => $this->appInstance->req->attrs->request['recaptcha_challenge_field'],
			'response' => $this->appInstance->req->attrs->request['recaptcha_response_field'],
    ));
		$this->writeln('POST /recaptcha/api/verify HTTP/1.0');
		$this->writeln('Host: www.google.com');
		$this->writeln('Content-type: application/x-www-form-urlencoded');
		$this->writeln('Content-length: '.strlen($body));
		$this->writeln('User-Aegent: reCAPTCHA/phpDaemon');
		$this->writeln('');
		$this->write($body);
		$this->onResponse[] = $cb;
	}
	public function writeln($s) {
		parent::writeln($s);
	}
	/**
	 * Called when new data received
	 * @param string New data
	 * @return void
	 */
	public function stdin($buf) {
		if ($this->pstate === self::PSTATE_BODY) {
			goto body;
		}
		$this->buf .= $buf;
		while (($line = $this->gets()) !== FALSE) {
			if ($line === '') {
				$this->body .= $this->buf;
				$this->buf = '';
				$this->pstate = self::PSTATE_BODY;
				break;
			}
			if ($this->pstate === self::PSTATE_FIRSTLINE) {
				$this->headers['STATUS'] = $line;
				$this->pstate = self::PSTATE_HEADERS;
			} else {
				$e = explode(': ',$line);
				
				if (isset($e[1])) {
					$this->headers['HTTP_' . strtoupper(strtr($e[0], HTTPRequest::$htr))] = $e[1];
				}
			}
		}
		if (isset($this->headers['HTTP_CONTENT_LENGTH'])) {
			$this->contentLength = (int) $this->headers['HTTP_CONTENT_LENGTH'];
			
		}
		body:
		$this->body .= $this->buf;
		if (strlen($this->body) >= $this->contentLength) {
			list($resultP,$text) = explode("\n",$this->body);
			$result = $resultP === 'true';
		
			$f = array_shift($this->onResponse);

			if ($f) {
				call_user_func($f, $result, $text);
			}
		}
	}
	/**
	 * Called when session finishes
	 * @return void
	 */
	public function onFinish() {
		$this->finished = TRUE;

		unset($this->appInstance->servConn[$this->addr][$this->connId]);
		unset($this->appInstance->sessions[$this->connId]);
	}
}

