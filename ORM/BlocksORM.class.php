<?php

/**
 * BlocksORM
 */
class BlocksORM extends ORM {

	public $blocks;

	public function init() {
		$this->blocks = $this->appInstance->db->{$this->appInstance->dbname . '.blocks'};
	}
	public function getPage($locale,$path,$cb) {
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
		$block['mtime'] = microtime(true);
		if (!isset($block['locale'])) {$block['locale'] = null;}
		if (isset($block['_id'])) {
			$find = array('_id' => $block['_id']);
		}
		elseif (isset($block['path'])) {
			$find = array('locale' => $block['locale'], 'path' => $block['path']);
		}
		else {
			$find = array('name' => $block['name']);
		}
		unset($block['_id']);
		if (isset($block['template'])) {
		
			$tpl = $this->appInstance->getQuickyInstance();
			$tpl->register_function('getblock',function($args) {});
			$block['templatePHP'] =	$tpl->_compile_string($block['template'],implode(':',$find));
		}
		$this->blocks->upsert($find,array('$set' => $block));
	}
}
