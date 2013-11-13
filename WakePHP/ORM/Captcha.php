<?php
namespace WakePHP\ORM;

use PHPDaemon\Clients\Mongo\Collection;
use PHPDaemon\Core\Daemon;
use PHPDaemon\Core\Debug;
use WakePHP\ORM\Generic;

/**
 * Captcha
 */
class Captcha extends Generic {

	/** @var Collection */
	protected $captcha;

	/**
	 *
	 */
	public function init() {
		$this->captcha = $this->appInstance->db->{$this->appInstance->dbname . '.captcha'};
	}

	/**
	 * @param array $find
	 * @param callable $cb
	 */
	public function newToken($cb, $add = []) {
		$this->captcha->insertOne([
			'_id' => $id = new \MongoId,
			'rnd' => $rnd = \PHPDaemon\Utils\Crypt::randomString(8),
			'text' => $text = \WakePHP\Utils\CaptchaDraw::getRandomText(),
			'ctime' => time(),
		] + $add, function ($lastError) use ($id, $rnd, $cb, $text) {
			if (!$lastError['ok']) {
				call_user_func($cb, false);
				return;
			}
			$token = base64_encode($id. "\x00" . $rnd);
			$this->appInstance->JobManager->enqueue(function($result) use ($token, $text, $cb) {
				if (!$result) {
					call_user_func($cb, false);
					return;
				}
				Daemon::log(Debug::dump([[$result]]));
				call_user_func($cb, $token);
			}, 'GenerateCaptchaImage', [$token, $text]);
		});
	}



	/**
	 * @param $names
	 * @param callable $cb
	 */
	public function uploadImage($token, $img, $cb) {
		$e = static::decodeToken($token);
		if ($e === false) {
			call_user_func($cb, false);
		}
		list ($id, $rnd) = $e;
		$this->captcha->updateOne([
			'_id' => $id,
			'rnd' => $rnd,
		], ['$set' => [
			'img' => new \MongoBinData($img, \MongoBinData::BYTE_ARRAY),
		]], $cb);
	}

	public static function decodeToken($token) {
		$d = base64_decode($token);
		if (!strlen($d)) {
			return false;
		}
		$e = explode("\x00", $d, 2);
		if (sizeof($e) < 2) {
			return false;
		}
		if (!ctype_xdigit($e[0])) {
			return false;
		}
		$e[0] = new \MongoId($e[0]);
		return $e;
	}

	/**
	 * @param $names
	 * @param callable $cb
	 */
	public function get($token, $cb) {
		$e = static::decodeToken($token);
		if ($e === false) {
			call_user_func($cb, 'badToken');
			return;
		}
		list ($id, $rnd) = $e;
		$this->captcha->findOne($cb, ['where' => [
			'_id' => $id,
			'rnd' => $rnd,
		]]);
	}

	/**
	 * @param $names
	 * @param callable $cb
	 */
	public function check($token, $text, $invalidate = true, $cb) {
		$e = static::decodeToken($token);
		if ($e === false) {
			call_user_func($cb, 'badToken');
			return;
		}
		list ($id, $rnd) = $e;
		$this->captcha->findOne(function ($t) use ($cb, $id, $text, $invalidate) {
			if (!$t) {
				call_user_func($cb, 'expired');
				return;
			}
					Daemon::log(Debug::dump([$invalidate]));
			if (!$invalidate) {
				if (strtolower($t['text']) === strtolower($text)) {
					call_user_func($cb, 'ok');
					return;
				}
			}
			$this->captcha->remove(['_id' => new \MongoId($id)], function($lastError) use ($t, $text, $cb) {
				if ($lastError['n'] !== 1) {
					call_user_func($cb, 'expired');
					return;	
				}
				if (strtolower($t['text']) !== strtolower($text)) {
					call_user_func($cb, 'incorrect');
					return;
				}
				call_user_func($cb, 'ok');
			});
		}, ['where' => [
			'_id' => $id,
			'rnd' => $rnd,
			'ctime' => ['$gt' => time() - 3600],
		]]);
	}

}
