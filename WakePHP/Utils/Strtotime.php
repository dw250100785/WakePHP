<?php
namespace WakePHP\Utils;

/**
 * Strtotime class.
 */
class Strtotime {
	use \PHPDaemon\Traits\ClassWatchdog;
	public static function parse($str) {
		return strtotime($str);
	}

}
