<?php
namespace WakePHP\Actions;
use PHPDaemon\Core\CallbackWrapper;

/**
 * Class Generic
 * @package WakePHP\Actions
 * @dynamic_fields
 */
abstract class Generic {
	use \PHPDaemon\Traits\ClassWatchdog;

	protected $appInstance;
	protected $callback;
	protected $req;
	protected $cmp;

	public function setRequest($req) {
		$this->req = $req;
	}
	
	public function setComponent($cmp) {
		$this->cmp = $cmp;
	}

	public function __construct($params = []) {
		foreach ($params as $k => $v) {
			$this->{$k} = $v;
		}
	}

	public function setAppInstance($appInstance) {
		$this->appInstance = $appInstance;

	}

	public function setCallback($cb) {
		$this->callback = CallbackWrapper::wrap($cb);
	}

	abstract public function perform();
	
	public static function resultMap(&$m) {
		if (is_array($m)) {
			foreach ($m as &$v) {
				static::resultMap($v);
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
