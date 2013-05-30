<?php
namespace WakePHP\Components;

use WakePHP\Core\Component;

/**
 * Statistics class
 * db.pagehits.ensureIndex({url: 1, ts: 1});
 * db.pagehits.ensureIndex({uid: 1});
 */
class Statistics extends Component {

	/**
	 * Request request in DB
	 */
	public function execute() {
		$this->pagehits->insert(array(
									'url'     => $this->req->attrs->server['DOCUMENT_URI'],
									'country' => $this->req->attrs->server['GEOIP_COUNTRY_CODE'],
									'ip'      => $this->req->attrs->server['REMOTE_ADDR'],
									'agent'   => $this->req->attrs->server['HTTP_USER_AGENT'],
								));
	}

}
