<?php
namespace WakePHP\Core;

/**
 * Class Crypt
 * @package WakePHP\Core
 */
class Crypt {
	/**
	 * Generate keccak hash for string with salt
	 * @param string $str
	 * @param string $salt
	 * @return string
	 */
	public static function hash($str, $salt = '') {
		$n = 512;
		if (strncmp($salt, '$', 1) === 0) {
			$e = explode('$', $salt, 3);
			if (ctype_digit($e[1])) {
				$n = (int)$e[1];
			}
		}
		return base64_encode(keccak_hash($str . $salt, $n));
	}

	public static function randomString($len = 64, $chars = 'ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789_-.:') {
		$r = '';
		$m = strlen($chars) - 1;
		for ($i = 0; $i < $len; ++$i) {
			$r .= $chars[mt_rand(0, $m)];
		}
		return $r;
	}
}

