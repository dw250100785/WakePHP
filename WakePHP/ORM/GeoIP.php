<?php
namespace WakePHP\ORM;

use PHPDaemon\Clients\Mongo\Collection;
use PHPDaemon\Core\Daemon;
use PHPDaemon\Core\Debug;
use PHPDaemon\Utils\Crypt;

/**
 * Class GeoIP
 * @package WakePHP\ORM
 */
class GeoIP extends Generic {
	protected $dbname = 'GeoIP';

	/** @var  Collection */
	protected $blocks;

	protected $locations;

	/**
	 *
	 */
	public function init() {
		$this->blocks = $this->appInstance->db->{$this->dbname . '.blocks'};
		$this->locations = $this->appInstance->db->{$this->dbname . '.locations'};
	}

	/**
	 * @param string $ip
	 * @param callable $cb
	 */
	public function query($ip, $cb) {
		if (is_string($ip)) {
			$ip = ip2long($ip);
		}
		$this->blocks->findOne(function($blk) use ($cb) {
			if (!$blk) {
				call_user_func($cb, false);
				return;
			}
			$this->locations->findOne(function($loc) use ($cb) {
				call_user_func($cb, $loc);
			}, ['where' => ['_id' => $blk['l']]]);
		}, [
			'where' => ['s' => ['$lte' => $ip]],
			'sort' => ['s' => -1]
		]);
	}
}
