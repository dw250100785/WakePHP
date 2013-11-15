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
 * SMS component
 * @method onSessionStart(callable $cb)
 * @method onAuth(callable $cb)
 */
class SMS extends Component {

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

	protected static $STATUS_CODES = [
		-1 => 'QUEUED',
		0 => 'SENT_TO_OP',
		1 => 'DELIVERED',
		3 => 'EXPIRED',
		20 => 'NOT_DELIVERED',
		22 => 'BAD_NUMBER',
		23 => 'FORBIDDEN',
		24 => 'NO_CREDIT',
		25 => 'NO_ROUTE',
	];

	protected static $ERROR_CODES = [
		9 => 'THROTTLED',
		4 => 'BANNED',
		3 => 'NOT_FOUND',
		2 => 'ACCESS_RESTRICTED',
		1 => 'BAD_PARAMS',
	];

	/**
	 * @return bool
	 */
	public function checkReferer() {
		if ($this->req->controller === 'Send') {
			return true;
		}
		if ($this->req->controller === 'Status') {
			return true;
		}
		return $this->req->checkDomainMatch();
	}

	public function StatusController() {
		$phone = Request::getString($_REQUEST['phone']);
		$idText = Request::getString($_REQUEST['idText']);
		$this->appInstance->sms->getMessage([
			'phone' => $phone,
			'idText' => $idText,
		], function ($msg) {
			if (!$msg) {
				$this->req->setResult(['success' => false, 'errcode' => 'NOT_FOUND']);
				return;
			}
			$this->status($msg->getPhone(), $msg->getId(), function($ret) {
				if (isset($ret['error_code'])) {
					$this->req->setResult([
						'success' => false,
						'errcode' => isset(static::$ERROR_CODES[$ret['error_code']]) ? static::$ERROR_CODES[$ret['error_code']] : 'UNK'
					]);
					return;
				}
				if (isset($ret['status'])) {
					$this->req->setResult([
						'success' => true,
						'status' => isset(static::$STATUS_CODES[$ret['status']]) ? static::$STATUS_CODES[$ret['status']] : 'UNK',
						'lastTs' => isset($ret['last_timestamp']) && is_int($ret['last_timestamp']) ? $ret['last_timestamp'] : false
					]);
				} else {
					$this->req->setResult(['success' => false]);
				}				
			});
		});
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
