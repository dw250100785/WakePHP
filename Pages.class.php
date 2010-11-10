<?php

/**
 * Pages.
 * db.pages.ensureIndex({name:1},{unique:true});	
 */
class Pages {

	public function __construct($appInstance) {
		$this->appInstance = $appInstance;
		$this->pages = $this->appInstance->db->{$this->appInstance->dbname . '.pages'};
	}

	public function getPage($lang,$path,$cb) {
		$this->pages->findOne($cb,array(
				'where' => array(
										'lang' => $lang,
										'path' => $path,
									),
		));
	}

}
