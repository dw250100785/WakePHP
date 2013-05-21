<?php
namespace WakePHP\Core;

class Crypt {
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
}

