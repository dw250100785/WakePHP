<?php
namespace WakePHP\Objects\Proxy;

use PHPDaemon\Core\Daemon;
use PHPDaemon\Core\Debug;
use WakePHP\Objects\Generic;

/**
 * Class Source
 * @package WakePHP\Objects
 */
class Source extends Generic {
	public static function ormInit($orm) {
		$orm->sources = $orm->appInstance->db->{$orm->appInstance->dbname . '.proxySources'};
		$orm->sources->ensureIndex(['service' => 1, 'username' => 1], ['unique' => true]);
	}

	protected function construct() {
		$this->col = $this->orm->sources;
	}

	protected function init() {
	}

	public function grab($cb = null) {
		$this->orm->appInstance->JobManager->enqueue($cb !== null ? function($res) use ($cb) {
			call_user_func($cb, $this, $res);
		} : null, 'Fetch' . $this['service'], [$this->toArray()], [
			'atmostonce' => 'Fetch-' . $this['service'].'—'.$this['username'],
			'priority' => -20,
		]);
	}
}
