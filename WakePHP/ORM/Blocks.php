<?php
namespace WakePHP\ORM;

use PHPDaemon\Clients\Mongo\Collection;
use PHPDaemon\Core\Daemon;
use PHPDaemon\Core\Debug;
use WakePHP\ORM\Generic;

/**
 * Blocks
 */
class Blocks extends Generic {

	/** @var Collection */
	public $blocks;

	/**
	 *
	 */
	public function init() {
		$this->blocks = $this->appInstance->db->{$this->appInstance->dbname . '.blocks'};
	}

	/**
	 * @param array $find
	 * @param callable $cb
	 */
	public function getBlock($find, $cb) {
		if (isset($find['_id']) && is_string($find['_id'])) {
			$find['_id'] = new \MongoId($find['_id']);
		}
		$this->blocks->findOne($cb, array('where' => $find));
	}

	/**
	 * @param $names
	 * @param callable $cb
	 */
	public function getBlocksByNames($names, $cb) {
		$this->blocks->find($cb, ['limit' => -100, 'where' => array('name' => array('$in' => $names))]);
	}

	/**
	 * @param $id
	 * @param callable $cb
	 */
	public function getBlockById($id, $cb) {
		$this->getBlock(array('_id' => $id), $cb);
	}

	/**
	 * @param $block
	 * @param bool $update
	 */
	public function saveBlock($block, $update = false) {
		$block['mtime'] = microtime(true);
		if (!isset($block['locale'])) {
			$block['locale'] = null;
		}
		if (isset($block['_id'])) {
			$find = array('_id' => $block['_id']);
		}
		elseif (isset($block['path'])) {
			$find = array('locale' => $block['locale'], 'path' => $block['path']);
		}
		else {
			$find = array('name' => (string)$block['name']);
		}
		unset($block['_id']);
		if (isset($block['template'])) {

			$tpl = $this->appInstance->getQuickyInstance();
			$tpl->register_function('getblock', function ($args) {
			});
			$block['templatePHP'] = 'return function($tpl) {
			$var = &$tpl->_tpl_vars;
			$config = &$tpl->_tpl_config;
			$capture = &$tpl->_block_props[\'capture\'];
			$foreach = &$tpl->_block_props[\'foreach\'];
			$section = &$tpl->_block_props[\'section\'];
			?>'
					//.$tpl->_compile_string('{capture name="w"}'.$block['template'].'{/capture}'
					. $tpl->_compile_string($block['template']
						, implode(':', $find))
					. '<?php };';
		}
		$block['cachekey'] = md5($block['templatePHP']);
		$this->blocks->upsertOne($find, $update ? array('$set' => $block) : $block);
	}
}
