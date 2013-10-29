<?php
namespace WakePHP\Actions\Account;

use WakePHP\Actions\Generic;
use PHPDaemon\Request\Generic as Request;
use PHPDaemon\Utils\Crypt;
use PHPDaemon\Core\Debug;

/**
 * Class GenKeccak
 * @package WakePHP\Actions\Account
 * @dynamic_fields
 */
class GenKeccak extends Generic {

	public function perform() {
		$str    = Request::getString($_REQUEST['str']);
		$size   = Request::getInteger($_REQUEST['size']);
		$rounds = Request::getInteger($_REQUEST['rounds']);
		if (!$rounds) {
			$rounds = 24;
		}
		$salt = '$512=24';
		$hash = Crypt::hash($str, $salt);
		$hex  = trim(str_replace('\\x', ' ', Debug::exportBytes(base64_decode($hash), true)));
		$this->req->setResult(['stringWithSalt' => $str . $salt, 'base64' => $hash, 'salt' => $salt, 'hex' => $hex, 'rounds' => 24]);
	}
}
