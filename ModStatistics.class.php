<?php
/* Statistics class

   db.pagehits.ensureIndex({url: 1, ts: 1});
   db.pagehits.ensureIndex({uid: 1});
  
 */
class ModStatistics extends Module {

	public function execute() {

		/* Register request in DB	*/
	
		$this->pagehits->insert(array(
			'url' => $req->attrs->server['DOCUMENT_URI'],
			'country' => $req->attrs->server['GEOIP_COUNTRY_CODE'],
			'ip' => $req->attrs->server['REMOTE_ADDR'],
			'agent' => $req->attrs->server['HTTP_USER_AGENT'],
		));
		
		
	}
}
