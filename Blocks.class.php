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
	public function getBlockByName($name, $cb) {
		$this->blocks->findOne($cb,array(
				'where' => array(
										'name' => $name,
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
					++$node->numBlocks;
					if (isset($attr['name'])) {
						$names[] = $attr['name'];
					}
				}
			}
		, null);
		$parse = xml_parse($parser,$node->html);
		xml_parser_free($parser);
		
		$names = array_unique($names);
		
		++$node->req->jobTotal;
		$this->blocks->find(
			function($cursor) use ($node, $blocks) {
		
				static $dbprops = array();
	
				foreach ($cursor->items as $k => $block) {
					if (isset($block['name'])) {
						$dbprops[$block['name']] = $block;
					}
					unset($cursor->items[$k]);
				}
				
				if (!$cursor->finished) {
					$cursor->getMore();
				}	else {
					$cursor->destroy();
				
					foreach ($blocks as $block) {
						if (isset($block['name']) && isset($dbprops[$block['name']])) {
							$block = array_merge($block,$dbprops[$block['name']]);
						}
						if ((!isset($block['mod'])) || (!class_exists($class = 'Mod'.$block['mod']))) {
							$class = 'Block';
						}
						new $class($block,$node);
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
