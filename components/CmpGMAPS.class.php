<?php

/**
 * GMAPS component
 */
class CmpGMAPS extends AsyncServer {

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

		$this->sessions[$connId] = new CmpGMAPSSession($connId, $this);
		$this->sessions[$connId]->addr = $addr;
		$this->servConn[$addr][$connId] = $connId;

		return $connId;
	}
	public function geo($q, $cb) {
		
		$connId = $this->getConnection('maps.google.com');
		$this->sessions[$connId]->geo($q, $cb);
		
	}
}

class CmpGMAPSSession extends SocketSession {

	const PSTATE_FIRSTLINE = 1;
	const PSTATE_HEADERS = 2;
	const PSTATE_BODY = 3;
	public $pstate = 1;
	public $headers = array();
	public $onResponse = array();
	public $contentLength = 0;
	public $body = '';
	public $EOL = "\r\n";
	public function geo($q, $cb) {    
		$this->writeln('GET /maps/geo?' . http_build_query(array(
			'q'				=> $q,
			'output'	=> 'json',
			'oe'			=> 'utf8',
			'sensor'	=> 'false',
			'key'			=> $this->appInstance->appInstance->config->googleapikey->value,
    )) . ' HTTP/1.0');
		$this->writeln('Host: maps.google.com');
		$this->writeln('User-Agent: GMAPS/phpDaemon');
		$this->writeln('');
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
			$result = json_decode(trim($this->body), true);

			$f = array_shift($this->onResponse);

			if ($f) {
				call_user_func($f, $result);
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

