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
	public $servers;

	/** @var  Collection */
	public $sources;

	/**
	 *
	 */
	public function init() {
		Objects\Server::ormInit($this);
		Objects\Source::ormInit($this);
	}
}
