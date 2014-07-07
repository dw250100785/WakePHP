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

class FetchFineproxy extends Generic {
	protected $proxy;
	protected $op;
	protected $destfolder;
	protected $params;
	public function run() {
		$this->proxy = $this->orm->appInstance->proxy;
		$this->params = $this['args'][0];
		try {
			$this->orm->appInstance->httpclient->post('https://account.fineproxy.org/proxy/download/http_auth/txt/', [
				'log' => $this->params['username'],
				'pass' => $this->params['password'],
				'logsub' => 'Войти',
			], function($conn) {
				$proxies = [];
				foreach (explode("\n", $conn->body) as $addr) {
					$addr = trim($addr);
					if (!preg_match('~^\d+\.\d+\.\d+\.\d+:\d+$~', $addr)) {
						continue;
					}
					$proxies[] = [
						'type' => 'http',
						'addr' => $addr,
						'auth' => [
							'username' => $this->params['username'], 
							'password' => $this->params['password'], 
					]];
				}
				$source = 'Fineproxy-' . $this->params['username'];
				$itime = time();
				$j = (new ComplexJob(function($j) use ($source, $itime) {
					$this->sendResult(true);
					$this->proxy->removeProxyServer([
						'source' => $source,
						'itime' => ['$lt' => $itime]
					]);
					Daemon::log('complete');
				}))
				->maxConcurrency(5)
				->more(function() use (&$proxies, $source, $itime) {
					 foreach ($proxies as $k => $proxy) {
					 	yield 'proxy_'.$k => function($jobname, $j) use ($proxy, $itime) {
					 		$this->proxy->newProxyServer($proxy)->setOnInsertMode(false)->attr([
					 			'itime' => $itime,
					 		])->save(function ($o)  use ($jobname, $j) {
								$j->setResult($jobname, $o->lastError());
					 		});			 		
					 	};
					 }
				});
				$j();
			});
		} catch (Exception $e) {
			$this->sendResult(false);
		}
	}
}
