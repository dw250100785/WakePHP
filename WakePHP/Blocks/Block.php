<?php
namespace WakePHP\Blocks;

use PHPDaemon\Clients\Mongo\Cursor;
use PHPDaemon\Core\Daemon;
use PHPDaemon\Core\Debug;
use WakePHP\Core\Request;

/**
 * Block instance class.
 * @property string cachekey
 * @dynamic_fields
 */
class Block implements \ArrayAccess {
	use \PHPDaemon\Traits\ClassWatchdog;

	/**
	 * @var string
	 */
	public $html = '';

	/**
	 * @var array
	 */
	public $tplvars = array();

	/**
	 * @var int
	 */
	public $readyBlocks = 0;
	/**
	 * @var int
	 */
	public $numBlocks = 0;

	/**
	 * @var mixed
	 */
	public $_nid;

	/**
	 * @var
	 */
	public $bid;

	/**
	 * @var bool
	 */
	public $nowrap = false;

	/**
	 * @var
	 */
	public $parentNode;

	/**
	 * @var array
	 */
	public $inner = array();
	/** @var  Request */
	public $req;

	/**
	 * @var bool
	 */
	public $ready = false;

	/**
	 * @var
	 */
	public $name;

	/**
	 * @var array
	 */
	public $addedBlocks = [];
	/**
	 * @var array
	 */
	public $addedBlocksNames = [];
	/**
	 * @var array
	 */
	public $attrs = [];

	/**
	 * @param mixed $offset
	 * @param mixed $value
	 */
	public function offsetSet($offset, $value) {
	}

	/**
	 * @param mixed $offset
	 * @return bool
	 */
	public function offsetExists($offset) {
		return isset($this->{$offset});
	}

	/**
	 * @param mixed $offset
	 */
	public function offsetUnset($offset) {
	}

	/**
	 * @param mixed $offset
	 * @return mixed
	 */
	public function offsetGet($offset) {
		return $this->{$offset};
	}

	/**
	 * @param array $attrs
	 * @param Block $parentNode
	 * @param bool $cold
	 */
	public function __construct($attrs, $parentNode, $cold = false) {
		$this->parentNode = $parentNode;
		$this->req        = $this->parentNode instanceof Request ? $this->parentNode : $this->parentNode->req;

		$this->parentNode->inner[] = $this;

		end($this->parentNode->inner);
		$this->_nid = key($this->parentNode->inner);

		foreach ($attrs as $key => $value) {
			$this->{$key} = $value;
		}
		$this->attrs = $attrs;
		unset($this->attrs['template'], $this->attrs['templatePHP'], $this->attrs['cachekey'], $this->attrs['tag']);

		if ($cold) {
			return;
		}

		if (!$this->req->getBlock($this)) {
			$this->init();
		}
	}

	/**
	 * @return array
	 */
	public function exportObject() {
		return $this->attrs;
	}

	/**
	 *
	 */
	public function init() {
		$this->runTemplate();
	}

	/**
	 * @param $k
	 * @param $v
	 */
	public function assign($k, $v) {
		//call_user_func_array(array($this->req->tpl, 'assign'), func_get_args());
		$this->tplvars[$k] = $v;
		// @TODO: more flexible arguments order
	}

	protected function executeTemplate() {
		$tpl = $this->req->tpl;
		$tpl->assign('block', $this);
		$tpl->assign($this->tplvars);
		$tpl->register_function('getblock', array($this, 'getBlock'));
		static $cache = array();
		if (isset($cache[$this->cachekey])) {
			$cb = $cache[$this->cachekey];
		}
		else {
			$cb = eval($this->templatePHP);
			//\PHPdaemon\Core\Daemon::log(\PHPdaemon\Core\Debug::dump($this->templatePHP));
			$cache[$this->cachekey] = $cb;
		}
		ob_start();
		call_user_func($cb, $tpl);
		$this->html = ob_get_contents();
		ob_end_clean();
		//$this->html = $tpl->_block_props['capture']['w'];
	}

	protected function getNestedBlocks() {
		++$this->req->jobTotal;
		$this->req->appInstance->blocks->getBlocksByNames(array_unique($this->addedBlocksNames), function ($cursor) {
			Daemon::log('got cursor');
			/** @var Cursor $cursor */
			if (!$cursor->finished) {
				$cursor->getMore();
			}
			else {
				$dbprops = array();
				foreach ($cursor->items as $k => $block) {
					if (isset($block['name'])) {
						$dbprops[$block['name']] = $block;
					}
				}
					$cursor->destroy();
					foreach ($this->addedBlocks as $block) {
						if (isset($block['name']) && isset($dbprops[$block['name']])) {
							$block = array_merge($block, $dbprops[$block['name']]);
						}
						if ((!isset($block['type'])) || (!class_exists($class = __NAMESPACE__ . '\\Block' . $block['type']))) {
							$class = __NAMESPACE__ . '\\Block';
						}
						new $class($block, $this);
				}
				unset($this->addedBlocks);
				++$this->req->jobDone;
				$this->req->wakeup();
			}
		});
		$this->addedBlocksNames = null;
		$this->req->tpl->register_function('getblock', array($this->parentNode, 'getBlock'));
	}

	/**
	 *
	 */
	public function runTemplate() {
		if ($this->req->backendServerConn) {
			++$this->parentNode->readyBlocks;
			$this->req->backendServerConn->onReadyBlock($this);
			return;
		}
		$this->req->onWakeup();
		if (isset($this->template)) {
			$this->executeTemplate();
			$this->getNestedBlocks();
		}
		$req = $this->req;
		if ($this->readyBlocks >= $this->numBlocks) {
			$this->execute();
		}
		$req->onSleep();
	}

	/**
	 * @param $block
	 * @return string
	 */
	public function getBlock($block) {
		$block['tag']        = (string)new \MongoId;
		$this->addedBlocks[] = $block;
		if (isset($block['name'])) {
			$this->addedBlocksNames[] = $block['name'];
		}
		++$this->numBlocks;
		return $block['tag'];
	}

	/**
	 *
	 */
	public function execute() {
		$this->ready();
	}

	/**
	 * @param $obj
	 */
	public function onReadyBlock($obj) {
		if ($this->readyBlocks < $this->numBlocks) {
			return;
		}
		foreach ($this->inner as $k => $obj) {
			$obj->html = str_replace("\n", '', $obj->html);
			$this->html = str_replace($obj->tag, $obj->html, $this->html);
			unset($this->inner[$k]);
		}
		$this->execute();
	}

	/**
	 *
	 */
	public function ready() {
		if ($this->ready) {
			return;
		}
		$this->ready = true;
		if (!$this->nowrap) {
			$attrs = ' class="block ' .
					htmlspecialchars($this->name, ENT_QUOTES) .
					(isset($this->classes) ? ' ' . $this->classes : '') . '"';

			if (isset($this->id)) {
				$attrs .= ' id="' . htmlspecialchars($this->id, ENT_QUOTES) . '"';
			}
			elseif (isset($this->_id)) {
				$attrs .= ' id="' . htmlspecialchars($this->_id, ENT_QUOTES) . '"';
			}

			$this->html = '<div' . $attrs . '>' . $this->html . '</div>';
		}
		++$this->parentNode->readyBlocks;
		$this->parentNode->onReadyBlock($this);
		//$this->req = null;
		//$this->parentNode = null;
		//Daemon::log('ready '.get_class($this));
	}
}
