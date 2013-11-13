<?php
namespace WakePHP\Components;

use PHPDaemon\Clients\HTTP\Pool as HTTPClient;
use PHPDaemon\Clients\Mongo\Cursor;
use PHPDaemon\Core\ComplexJob;
use PHPDaemon\Core\Daemon;
use PHPDaemon\Core\CallbackWrapper;
use PHPDaemon\Core\Debug;
use PHPDaemon\Request\Generic as Request;
use WakePHP\Core\Component;
use WakePHP\Core\DeferredEventCmp;
use WakePHP\Core\Request as WakePHPRequest;

/**
 * SMSClient component
 * @method onSessionStart(callable $cb)
 * @method onAuth(callable $cb)
 */
class SMSClient extends Component {

	/**
	 * Function to get default config options from application
	 * Override to set your own
	 * @return array|bool
	 */
	protected function getConfigDefaults() {
		return array(
			'login' => '',
			'password' => '',
			'sender' => '',
		);
	}


	/**
	 * @return bool
	 */
	public function checkReferer() {
		if ($this->req->controller === 'Send') {
			return true;
		}
		return $this->req->checkDomainMatch();
	}

	public function SendController() {
		$phones = Request::getString($_REQUEST['phones']);
		$text = Request::getString($_REQUEST['text']);
		$this->send($phones, $text, function($result) {
			$this->req->setResult($result);
		});
	}
	public function send($phones, $text, $cb, $id = null) {
		$cb = CallbackWrapper::wrap($cb);
		$this->appInstance->httpclient->get($params = [
			'http://smsc.ru/sys/send.php',
			'login' => $this->config->login->value,
			'psw' => $this->config->password->value,
			'sender' => $this->config->sender->value,
			'phones' => $phones,
			'mes' => $text,
			'fmt' => 3,
			'charset' => 'utf-8',
		] + ($id !== null ? ['id' => $id] : []), function ($conn, $success) use ($cb) {
			call_user_func($cb, json_decode($conn->body, true));
		});
	}
	public function status($phone, $id, $cb, $all = 0) {
		$cb = CallbackWrapper::wrap($cb);
		$this->appInstance->httpclient->get([
			'http://smsc.ru/sys/status.php',
			'login' => $this->config->login->value,
			'psw' => $this->config->password->value,
			'phone' => $phone,
			'id' => $id,
			'fmt' => 3,
			'all' => $all,
		], function ($conn, $success) use ($cb) {
			call_user_func($cb, json_decode($conn->body, true));
		});
	}
}
