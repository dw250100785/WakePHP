<?php
namespace WakePHP\ORM;

use PHPDaemon\Clients\Mongo\Collection;
use PHPDaemon\Core\Daemon;
use PHPDaemon\Core\Debug;
use PHPDaemon\Utils\Crypt;
use WakePHP\Objects\Proxy as Objects;

/**
 * Class Proxy
 * @package WakePHP\ORM
 */
class Proxy extends Generic {
	/** @var  Collection */
	public $proxyServers;

	/** @var  Collection */
	public $proxySources;

	/**
	 *
	 */
	public function init() {
		Objects\ProxyServer::ormInit($this);
		Objects\ProxySource::ormInit($this);
	}
}
