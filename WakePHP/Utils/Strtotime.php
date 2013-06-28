<?php
namespace WakePHP\Utils;

/**
 * Strtotime class.
 */
class Strtotime {
	use \PHPDaemon\Traits\ClassWatchdog;
	use \PHPDaemon\Traits\StaticObjectWatchdog;
	public static function parse($str) {
		return strtotime($str);
	}

}
