<?php
namespace WakePHP\Actions\Captcha;

/**
 * Class Generic
 * @package WakePHP\Actions
 * @dynamic_fields
 */
abstract class Generic extends \WakePHP\Actions\Generic {
	use \PHPDaemon\Traits\ClassWatchdog;

	public function resultMap(&$m) {
		if (is_array($m)) {
			foreach ($m as &$v) {
				$this->resultMap($v);
			}
		} else {
			if ($m instanceof \MongoBinData) {
				$m = base64_encode($m->bin);
			}
			elseif ($m instanceof \MongoId) {
				$m = (string )$m;
			}
		}
	}
	
	
}
