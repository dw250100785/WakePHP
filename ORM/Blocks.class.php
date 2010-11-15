<?php

/**
 * Blocks.
 */
class Blocks extends ORM {

	public $blocks;

	public function init() {
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
	public function getBlockById($id, $cb) {
		$this->blocks->findOne($cb,array(
				'where' => array(
										'_id' => new MongoId($id),
				),
		));
	}
	public function saveBlock($block) {
		$tpl = $this->appInstance->getQuickyInstance();
		$tpl->register_function('getblock',function($args) {});
			
		if (isset($block['name'])) {
			$tplName = $block['name'];
		}
		elseif (isset($block['path'])) {
			$tplName = $block['lang'].'/'.$block['path'];
		}
		else
		{
		 return;
		}
		$block['templatePHP'] =	$tpl->_compile_string($block['template'],$tplName);
		$block['mtime'] = microtime(true);
		if (!isset($block['lang'])) {$block['lang'] = null;}
		if (isset($block['_id'])) {
			$find = array('_id' => $block['_id']);
		}
		elseif (isset($block['path'])) {
			$find = array('lang' => $block['lang'], 'path' => $block['path']);
		}
		else {
			$find = array('name' => $block['name']);
		}
		unset($block['_id']);
		$this->blocks->upsert($find,array('$set' => $block));
	}
}
