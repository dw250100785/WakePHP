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
		$orm->messages->ensureIndex(['code' => 1]);
	}

	protected function fetchObject($cb) {
		$this->orm->messages->findOne($cb, ['where' => $this->cond,]);
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
		$this->cond = [
			'_id'	=> $obj['id'],
			'phone' => $obj['phone'],
		];

	}
	public function setPhone($phone) {
		if (!preg_match('~^\+?\d+$~', $phone)) {
			throw Exception('Wrong phone number.');
		}
		$this->set('phone', $phone);
	}

	public function send($cb) {
		$this->orm->appInstance->components->SMSClient->send($this['phone'], $this['text'], function($res) use ($cb) {
			if (isset($res['id'])) {
				$this['_id'] = $res['id'];
				call_user_func($cb, $this, true);
			} else {
				call_user_func($cb, $this, false);
			}
		});
	}
	
	protected function saveObject($cb) {
		if ($this->new) {
			$set = $this->obj;
			unset($set['_id']);
			if ($this->cond === null) {
				$this->extractCondFrom($this->obj);
			}
			if (!sizeof($this->cond)) {
				if ($cb !== null) {
					call_user_func($cb, false);
				}
				return;
			}
			$this->orm->accounts->upsertOne($this->cond, ['$set' => $set], $cb);
		} else {
			$this->orm->accounts->upsertOne($this->cond, $this->update, $cb);
		}
	}

}
