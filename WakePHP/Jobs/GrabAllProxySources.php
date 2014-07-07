<?php
namespace WakePHP\Jobs;

use PHPDaemon\Core\Daemon;
use PHPDaemon\Core\Debug;
use PHPDaemon\Core\ComplexJob;
use PHPDaemon\FS\FileSystem;
use WakePHP\Utils\BitTorrentFile;
use PHPDaemon\Core\ShellCommand;
use PHPDaemon\Utils\Crypt;
use PHPDaemon\Clients\HTTP\Pool as HTTPClient;
use WakePHP\Exceptions\NotFound;

class GrabAllProxySources extends Generic {
	protected $orm;
	protected $proxy;
	public function run() {
		$this->proxy = $this->orm->appInstance->proxy;
		$this->proxy->getProxySource(['$or' => [['itime' => null], ['itime' => ['$lt' => time() - 5*60]]]])
		->fetchMulti(function($it) {
			foreach ($it as $ps) {
				$ps->grab();
			}
		});
		$this->sendResult(true);
		$this->orm->appInstance->JobManager->enqueue(null, 'GrabAllProxySources', [], [
			'atmostonce' => 'GrabAllProxySources',
			'notbefore' => strtotime('+5 minutes'),
			'priority' => -20,
		]);
	}
}
