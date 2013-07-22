<?php
namespace WakePHP\ORM;

use PHPDaemon\Clients\Mongo\Collection;
use WakePHP\ORM\Generic;

/**
 * Captcha
 */
class Captcha extends Generic {

	/** @var Collection */
	protected $captcha;

	/**
	 *
	 */
	public function init() {
		$this->captcha = $this->appInstance->db->{$this->appInstance->dbname . '.captcha'};
	}

	/**
	 * @param array $find
	 * @param callable $cb
	 */
	public function getToken($find, $cb) {
		$this->blocks->insertOne([
			'_id' => $id = new \MongoId,
			'add' => $add = \PHPDaemon\Utils\Crypt::randomString(8),
			'text' => \PHPDaemon\Utils\CaptchaDraw::getRandomText(),
		], function ($lastError) use ($id, $add, $cb) {
			$token = base64_encode(((string) $id). "\x00" . $add);
			call_user_func($cb, $token);
		});
	}

	/**
	 * @param $names
	 * @param callable $cb
	 */
	public function checkCaptcha($token, $text, $cb) {
		$this->blocks->findOne($cb, ['where' => '']);
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
		$this->blocks->upsert($find, $update ? array('$set' => $block) : $block);
	}
}
