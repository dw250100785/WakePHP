<?php
/* Класс отвечающий за статистку.

   db.pagehits.ensureIndex({url: 1, ts: 1});
   db.pagehits.ensureIndex({uid: 1});
  
 */
class xEstatistics {

	public $appInstance;
	public $pagehits;
	
	public function __construct($appInstance) {
		$this->appInstance = $appInstance;
		$this->pagehits = $appInstance->db->{$appInstance->dbname.'.pagehits'};
	}
	
	/* Регистрирует запрос в базе данных
	*/
	public function registerRequest($req) {
	
		$this->pagehits->insert(array(
			'url' => $req->attrs->server['DOCUMENT_URI'],
			'country' => $req->attrs->server['GEOIP_COUNTRY_CODE'],
			'ip' => $req->attrs->server['REMOTE_ADDR'],
			'agent' => $req->attrs->server['HTTP_USER_AGENT'],
		));
		
		
	}
}
