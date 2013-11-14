<?php
namespace WakePHP\Objects\SMS;
use PHPDaemon\Utils\Crypt;
use PHPDaemon\Core\Daemon;
use PHPDaemon\Core\Debug;

use WakePHP\Objects\Generic;

/**
 * Class Message
 * @package WakePHP\Objects\SMS
 */
class Message extends Generic {
	
	public function init() {
	}

	public static function ormInit($orm) {
		$orm->messages  = $orm->appInstance->db->{$orm->appInstance->dbname . '.smsmessages'};
		$orm->messages->ensureIndex(['phone' => 1]);
		$orm->messages->ensureIndex(['accountId' => 1]);
	}

	protected function fetchObject($cb) {
		$this->orm->messages->findOne($cb, ['where' => $this->cond,]);
	}

	public function genId($cb) {
		$this->orm->messages->autoincrement(function($seq) use ($cb) {
			$this->setId($seq);
			call_user_func($cb, $this);
		}, true);
	}

	protected function removeObject($cb) {
		if (!sizeof($this->cond)) {
			if ($cb !== null) {
				call_user_func($cb, false);
			}
			return;
		}
		$this->orm->accounts->remove($this->cond);
	}

	protected function countObject($cb) {
		if (!sizeof($this->cond)) {
			if ($cb !== null) {
				call_user_func($cb, false);
			}
			return;
		}
		$this->orm->accounts->count($this->cond);
	}

	public function extractCondFrom($obj) {
		$this->cond = [];
		if (isset($obj['phone'])) {
			$this->cond['phone'] = $obj['phone'];
		}
		if (isset($obj['_id'])) {
			$this->cond['_id'] = $obj['_id'];
		} elseif (isset($obj['idText'])) {
			$this->cond['idText'] = $obj['idText'];
		}

	}
	
	public function antiflood($cb) {
		$this->orm->messages->count(function($res) use ($cb) {
			call_user_func($cb, $this, $res['n'] > 5);
		}, [ 'where' => [
			'accountId' => $this['accountId'],
			'ts' => ['$gt' => microtime(true) - 15*60,]
		]]);
	}

	public function setId($v) {
		$this->set('_id', $v);
		$this->set('idText', sprintf('%04d', (($v - 1) % 10000) + 1));
		$this->set('code', Crypt::randomString(5, '1234567890'));
		$this->set('ts', microtime(true));
		$this->set('tries', 10);
	}

	public function getCode() {
		return '*SECRET*';
	}

	public function setMTAN($tpl) {
		$this->setText(sprintf($tpl, $this['idText'], $this->obj['code']));
		return $this;
	}
	public function checkCode($code, $cb) {
		if ($this->cond === null) {
			$this->extractCondFrom($this->obj);
		}
		$this->orm->messages->findAndModify([
			'query' => $this->cond + [
				'tries' => ['$gt' => 0],
				'ts' => ['$gt' => microtime(true) - 5*60],
				'success' => null,
			],
			'update' => ['$inc' => ['tries' => -11]],
			'new' => true,
		], function ($lastError) use ($cb, $code) {
			if (!isset($lastError['value']['code'])) {
				call_user_func($cb, $this, false, 0);
				return;
			}
			if (!Crypt::compareStrings($lastError['value']['code'], trim($code))) {
				call_user_func($cb, $this, false, $lastError['value']['tries']);
			}
			$this->set('success', true);
			$this->save(function() use ($cb) {
				if ($this->getLastError(true)) {
					call_user_func($cb, $this, true);
				} else {
					call_user_func($cb, $this, false, 0);
				}
			});
		});
	}

	public function setPhone($phone) {
		if (!preg_match('~^\+?\d+$~', $phone)) {
			throw new \Exception('Wrong phone number.');
		}
		$this->set('phone', $phone);
		return $this;
	}

	public function send($cb) {
		$this->save(function() use ($cb) {
			$this->orm->appInstance->components->SMS->send($this['phone'], $this['text'], function($res) use ($cb) {
				if (isset($res['id'])) {
					call_user_func($cb, $this, true);
				} else {
					call_user_func($cb, $this, false);
				}
			}, $this['_id']);
			return $this;
		});
	}
	
	protected function saveObject($cb) {
		if ($this->new) {
			$this->orm->messages->insertOne($this->obj, $cb);
		} else {
			$this->orm->messages->upsertOne($this->cond, $this->update, $cb);
		}
	}

}
