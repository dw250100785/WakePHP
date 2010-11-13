<?php

/**
 * Blocks.
 * db.blocks.ensureIndex({name:1},{unique:true});	
 */
class Blocks {

	public function __construct($appInstance) {
		$this->appInstance = $appInstance;
		$this->blocks = $this->appInstance->db->{$this->appInstance->dbname . '.blocks'};
	}
	public function getPage($lang,$path,$cb) {
		$this->blocks->findOne($cb,array(
				'where' => array(
										'path' => $path,
				),
		));
	}
	public function saveBlock($page) {
		$page['templatePHP'] =	$this->appInstance->getQuickyInstance()->_compile_string($page['template'],$page['name']);
		$page['mtime'] = microtime(true);
		if (!isset($page['lang'])) {$page['lang'] = null;}
		if (isset($page['path'])) {
			$find = array('lang' => $page['lang'], 'path' => $page['path']);
		}
		else {
			$find = array('name' => $page['name']);
		}
		$this->blocks->upsert($find,array('$set' => $page));
	}
	public function parse($node) {
		$blocks = array();
		$names = array();
		
		$parser = xml_parser_create();
		xml_parser_set_option($parser, XML_OPTION_CASE_FOLDING, 0);
		xml_set_element_handler($parser, 
			function ($parser, $tag, $attr) use (&$blocks, &$names, $node) {
				if (strtoupper($tag) === 'BLOCK') {
					$attr['tag'] = substr($node->html, $sp = strrpos($node->html, '<', ($ep = xml_get_current_byte_index($parser)+2) - strlen($node->html)), $ep - $sp);
				$blocks[] = $attr;
				if (isset($attr['name'])) {
					$names[] = $attr['name'];
				}
			}
		}, null);
		$parse = xml_parse($parser,$node->html);
		xml_parser_free($parser);
		
		$names = array_unique($names);
		
		++$node->req->jobTotal;
		$this->blocks->find(
			function($cursor) use ($node, $blocks) {
		
				static $dbprops = array();
	
				foreach ($cursor->items as $k => $ph) {
					if (isset($ph['name'])) {
						$dbprops[$ph['name']] = $ph;
					}
					unset($cursor->items[$k]);
				}
				
				if (!$cursor->finished) {
					$cursor->getMore();
				}	else {
					$cursor->destroy();
				
					foreach ($blocks as $ph) {
						if (isset($ph['name']) && isset($dbprops[$ph['name']])) {
							$ph = array_merge($ph,$dbprops[$ph['name']]);
						}
					
						new Block($ph,$node);
					}				
				
					++$node->req->jobDone;
					$node->req->wakeup();
				}
			}, array(
				'where' => array('name' => array('$in' => $names))
			)
		);
	}

}
