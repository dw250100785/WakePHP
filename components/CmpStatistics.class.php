<?php
namespace WakePHP\components;

use WakePHP\core\Component;

/**
 * Statistics class
 * db.pagehits.ensureIndex({url: 1, ts: 1});
 * db.pagehits.ensureIndex({uid: 1});
 */
class CmpStatistics extends Component {

	/**
	 * Request request in DB
	 */
	public function execute() {
		$this->pagehits->insert(array(
			'url'     => $req->attrs->server['DOCUMENT_URI'],
			'country' => $req->attrs->server['GEOIP_COUNTRY_CODE'],
			'ip'      => $req->attrs->server['REMOTE_ADDR'],
			'agent'   => $req->attrs->server['HTTP_USER_AGENT'],
		));	
	}

}
