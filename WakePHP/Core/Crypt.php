<?php
namespace WakePHP\Core;

class Crypt {
	protected static function hash($str, $salt) {
		$n = 1600;
		if (strpos($salt, '$') === 0) {
			$e = explode($salt, 3);
			if (ctype_digit($e[1])) {
				$n = (int) $e[1];
			}
		}
		return keccak_hash($str, $n);
	}	
}

