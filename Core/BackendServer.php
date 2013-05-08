<?php
namespace WakePHP\Core;

class BackendServer extends \PHPDaemon\Network\Server {
	/**
	 * Setting default config options
	 * Overriden from NetworkServer::getConfigDefaults
	 * @return array|false
	 */
	protected function getConfigDefaults() {
		return array(
			// @todo add description strings
			'listen'         => '0.0.0.0',
			'port'           => 9999,
			'defaultcharset' => 'utf-8',
			'expose'         => 1,
		);
	}

}

