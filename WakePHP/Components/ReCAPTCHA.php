<?php
namespace WakePHP\Components;

use PHPDaemon\Config\Entry;
use PHPDaemon\Request\Generic as Request;
use WakePHP\Core\Component;

/**
 * ReCAPTCHA component
 */
class ReCAPTCHA extends Component {

	/**
	 * Function to get default config options from application
	 * Override to set your own
	 * @return array|bool
	 */
	protected function getConfigDefaults() {
		return array(
			'privatekey' => new ConfigEntry(''),
		);
	}

	public function validate($cb) {

		if (empty($this->appInstance->req->attrs->request['recaptcha_challenge_field'])) {
			$cb(false, '');
			return;
		}
		if (empty($this->appInstance->req->attrs->request['recaptcha_response_field'])) {
			$cb(false, 'incorrect-captcha-sol');
			return;
		}
		if (empty($this->config->privatekey->value)) {
			$cb(false, 'empty-private-key');
			return;
		}

		$this->appInstance->httpclient->post('http://www.google.com/recaptha/api/verify', [
			'privatekey' => $this->config->privatekey->value,
			'remoteip'   => $_SERVER['REMOTE_ADDR'],
			'challenge'  => Request::getString($_REQUEST['recaptcha_challenge_field']),
			'response'   => Request::getString($_REQUEST['recaptcha_response_field']),
		], function ($conn, $success) use ($cb) {
			list($resultP, $text) = explode("\n", $conn->body);
			call_user_func($cb, $resultP === 'true', $text);
		});
		$body = http_build_query(array(
									 );
		$this->writeln('POST /recaptcha/api/verify HTTP/1.0');
		$this->writeln('Host: www.google.com');
		$this->writeln('Content-type: application/x-www-form-urlencoded');
		$this->writeln('Content-length: ' . strlen($body));
		$this->writeln('User-Agent: reCAPTCHA/phpDaemon');
		$this->writeln('');
		$this->write($body);
		$this->onResponse[] = $cb;
	}


	public static function checkJob() {
		return function ($jobname, $job) {
			$job->req->components->CAPTCHA->validate(function ($captchaOK, $msg) use ($jobname, $job) {

				$errors = array();
				if (!$captchaOK) {
					if ($msg === 'incorrect-captcha-sol') {
						$errors['captcha'] = 'Incorrect CAPTCHA solution.';
					}
					else {
						$errors['captcha'] = 'Unknown error.';
						$job->req->appInstance->log('CmpCaPTCHA: error: ' . $msg);
					}
				}

				$job->setResult($jobname, $errors);
			});
		};
	}

	/**
	 * Establishes connection
	 * @param string $addr Address
	 * @return integer Connection's ID
	 */
	public function getConnection($addr) {
		if (isset($this->servConn[$addr])) {
			foreach ($this->servConn[$addr] as &$c) {
				return $c;
			}
		}
		else {
			$this->servConn[$addr] = array();
		}

		$e = explode(':', $addr);

		if (!isset($e[1])) {
			$e[1] = 80;
		}
		$connId = $this->connectTo($e[0], (int)$e[1]);

		$this->sessions[$connId]        = new    CAPTCHASession($connId, $this);
		$this->sessions[$connId]->addr  = $addr;
		$this->servConn[$addr][$connId] = $connId;

		return $connId;
	}

	public function validate($cb) {

		$connId = $this->getConnection('www.google.com');
		$this->sessions[$connId]->validate($cb);

	}
}

