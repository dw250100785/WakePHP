<?php
namespace WakePHP\Core;

class BackendClient extends \PHPDaemon\Network\Client {
	/**
	 * Setting default config options
	 * Overriden from NetworkClient::getConfigDefaults
	 * @return array|false
	 */
	protected function getConfigDefaults() {
		return [
			// @todo add description strings
			'servers'        => '127.0.0.1',
			'port'           => 9999,
			'maxconnperserv' => 32
		];
	}
}

