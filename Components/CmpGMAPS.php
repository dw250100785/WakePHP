<?php
namespace WakePHP\Components;

use WakePHP\Core\Component;

/**
 * GMAPS component
 */
class CmpGMAPS extends Component {
	/**
	 * Constructor.
	 * @return void
	 */
	public function init() {
		$this->httpclient = \PHPDaemon\Clients\HTTP\Pool::getInstance();
	}

	/**
	 * Function to get default config options from application
	 * Override to set your own
	 * @return array|false
	 */
	protected function getConfigDefaults() {
		return false;
	}

	public function geo($q, $cb) {
		$this->httpclient->get(
			['http://maps.google.com/maps/geo',
				'q'      => $q,
				'output' => 'json',
				'oe'     => 'utf8',
				'sensor' => 'false',
			],
			function ($conn, $success) use ($cb) {
				call_user_func($cb, json_decode($conn->body, true));
			}
		);
	}
}

