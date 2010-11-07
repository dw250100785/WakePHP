<?php

/**
 * Placeholders.
 * db.placeholders.ensureIndex({name:1},{unique:true});	
 */
class Placeholders {

	public function __construct($appInstance) {
		$this->appInstance = $appInstance;
		$this->placeholders = $this->appInstance->db->{$this->appInstance->dbname . '.placeholders'};
	}

	public function parse($req) {
		$placeholders = array();
		$names = array();
		
		$parser = xml_parser_create();
		xml_parser_set_option($parser, XML_OPTION_CASE_FOLDING, 0);
		xml_set_element_handler($parser, 
			function ($parser, $tag, $attr) use (&$placeholders, &$names, $req) {
				if (strtoupper($tag) === 'PLACEHOLDER') {
					$attr['tag'] = substr($req->html, $sp = strrpos($req->html, '<', ($ep = xml_get_current_byte_index($parser)+2) - strlen($req->html)), $ep - $sp);
				$placeholders[] = $attr;
				$names[] = $attr['name'];
			}
		}, null);
		$parse = xml_parse($parser,$req->html);
		xml_parser_free($parser);
		
		$names = array_unique($names);
		
		++$req->jobTotal;
		$this->placeholders->find(
			function($cursor) use ($req, $placeholders) {
				/* hardcoded for testing purposes */
				$cursor->items = array(
					array('name' => 'trololo', 'inner' => array(
						array('mod' => 'Text', 'inner' => array('Hello world'))
					)),
					array('name' => 'footerStat', 'inner' => array(
						array('mod' => 'Pagetook', 'inner' => array('Page took %s'))
					)),
					array('name' => 'JSload', 'inner' => array(
						array('mod' => 'JSload')
					)),
				);
			
				static $dbprops = array();
	
				foreach ($cursor->items as $k => $ph) {
					$dbprops[$ph['name']] = $ph;
					unset($cursor->items[$k]);
				}
				
				if (!$cursor->finished) {
					$cursor->getMore();
				}	else {
					$cursor->destroy();
				
					foreach ($placeholders as $ph) {
						if (isset($dbprops[$ph['name']])) {
							$ph = array_merge($ph,$dbprops[$ph['name']]);
						}
					
						new Placeholder($ph,$req);
					}				
				
					++$req->jobDone;
					$req->wakeup();
				}
			}, array(
				'where' => array('name' => array('$in' => $names))
			)
		);
	}

}
