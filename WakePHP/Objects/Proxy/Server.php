<?php
namespace WakePHP\Objects\Proxy;

use PHPDaemon\Core\Daemon;
use PHPDaemon\Core\Debug;
use WakePHP\Objects\Generic;

/**
 * Class Server
 * @package WakePHP\Objects
 */
class Server extends Generic {
	protected $withAtomicCounter = false;
	protected $action = null;
	protected $upsertMode = true;

	public static function ormInit($orm) {
		$orm->servers = $orm->appInstance->db->{$orm->appInstance->dbname . '.proxyServers'};
		$orm->servers->ensureIndex(['addr' => 1,], ['unique' => true]);
	}

	public function getUserAgent() {
		return 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_9_2) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/34.0.1847.116 Safari/537.36';
	}

	protected function construct() {
		$this->col = $this->orm->servers;
	}


	protected function init() {
		if ($this->new) {
			$this->cond = ['addr' => $this['addr']];
		}
	}

	public function pickFor($action) {
		$this->upsertMode = false;
		$this->action = $action;
		$this->col->ensureIndex([
			'notbefore.' . $action => 1,
			'fails.' . $action => 1,
			'counter.' . $action => 1,
		]);
		$this->cond = ['$or' => [
			['notbefore.' . $action => null],
			['notbefore.' . $action => ['$lte' => time()]]
		]];
		$this->limit = 1;
		$this->sort = ['counter.' . $action => 1];
		return $this;
	}

	public function withAtomicCounter($cb) {
		$this->withAtomicCounter = true;
		$this->findAndModifyMode = true;
		$this->inc('counter.' . $this->action, 1);
		$this->save($cb);
		$this->findAndModifyMode = false;

	}

	public function reportFail($notbefore = null, $reason = 'network') {
		$this->extractCond();
		if ($notbefore !== null) {
			$this->set('notbefore.' . $this->action, $notbefore);
			$this->set('counter.' . $this->action, 0);
			$this->set('reason.' . $this->action, $reason);
		}
		$this->inc('fails.' . $this->action, 1);
		return $this;
	}

	public function reportSuccess() {
		$this->extractCond();
		if (!$this->withAtomicCounter) {
			$this->inc('counter.' . $this->action, 1);
		}
		$this->set('fails.' . $this->action, 0);
		$this->unsetProperty('reason.' . $this->action);
		return $this;
	}
}
