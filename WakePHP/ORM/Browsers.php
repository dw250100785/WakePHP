<?php
namespace WakePHP\ORM;

use PHPDaemon\Clients\Mongo\Collection;
use PHPDaemon\Core\Daemon;
use PHPDaemon\Core\Debug;
use WakePHP\ORM\Generic;

/**
 * Browsers
 */
class Browsers extends Generic {

	/** @var Collection */
	protected $browsers;

	/**
	 *
	 */
	public function init() {
		$this->browsers = $this->appInstance->db->{$this->appInstance->dbname . '.browsers'};
		\get_browser('Mozilla/5.0 (Macintosh; Intel Mac OS X 10_8_4) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/30.0.1599.66 Safari/537.36', true);
	}

	/**
	 * @param $names
	 * @param callable $cb
	 */
	public function get($agent, $cb) {
		$this->browsers->findOne(function ($item) use ($cb, $agent) {
			if ($item) {
				call_user_func($cb, $item);
				return;
			}
			$browser = \get_browser($agent, true);
			$browser['name'] = $browser['browser'];
			unset(
				$browser['browser_name_regex'],
				$browser['browser']
			);
			$this->browsers->insert(['_id' => $agent] + $browser);
			call_user_func($cb, $browser);
		}, ['where' => [
			'_id' => $agent,
		]]);
	}
}
