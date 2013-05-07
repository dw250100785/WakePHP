<?php
namespace WakePHP\core;

/**
 * Block instance class.
 */
class Block implements \ArrayAccess {

	public $html = '';

	public $tplvars = array();

	public $readyBlocks = 0;
	public $numBlocks = 0;

	public $_nid;

	public $bid;

	public $nowrap = false;

	public $parentNode;

	public $inner = array();
	/** @var  WakePHPRequest */
	public $req;

	public $ready = false;

	public $name;

	public $addedBlocks = [];
	public $addedBlocksNames = [];
	public $attrs = [];

	public function offsetSet($offset, $value) { }

	public function offsetExists($offset) {
		return isset($this->{$offset});
	}

	public function offsetUnset($offset) { }

	public function offsetGet($offset) {
		return $this->{$offset};
	}

	public function __construct($attrs, $parentNode, $cold = false) {
		$this->parentNode = $parentNode;
		$this->req        = $this->parentNode->req;

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

	public function exportObject() {
		return $this->attrs;
	}

	public function init() {
		$this->runTemplate();
	}

	public function assign($k, $v) {
		//call_user_func_array(array($this->req->tpl, 'assign'), func_get_args());
		$this->tplvars[$k] = $v;
		// @TODO: more flexible arguments order
	}

	public function runTemplate() {
		if ($this->req->backendServerConn) {
			++$this->parentNode->readyBlocks;
			$this->req->backendServerConn->onReadyBlock($this);
			return;
		}
		$this->req->onWakeup();
		if (isset($this->template)) {
			$tpl = $this->req->tpl;
			$tpl->assign('block', $this);
			$tpl->assign($this->tplvars);
			$tpl->register_function('getblock', array($this, 'getBlock'));
			static $cache = array();
			if (isset($cache[$this->cachekey])) {
				$cb = $cache[$this->cachekey];
			}
			else {
				$cb                     = eval($this->templatePHP);
				$cache[$this->cachekey] = $cb;
			}
			ob_start();
			call_user_func($cb, $tpl);
			$this->html = ob_get_contents();
			ob_end_clean();
			//$this->html = $tpl->_block_props['capture']['w'];

			++$this->req->jobTotal;
			$node = $this;
			$this->req->appInstance->blocks->getBlocksByNames(array_unique($this->addedBlocksNames), function ($cursor) use ($node) {
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
					foreach ($node->addedBlocks as $block) {
						if (isset($block['name']) && isset($dbprops[$block['name']])) {
							$block = array_merge($block, $dbprops[$block['name']]);
						}
						if ((!isset($block['type'])) || (!class_exists($class = 'Block' . $block['type']))) {
							$class = 'Block';
						}
						new $class($block, $node);
					}
					unset($node->addedBlocks);

					++$node->req->jobDone;
					$node->req->wakeup();
				}
			});
			unset($this->addedBlocksNames);
			$this->req->tpl->register_function('getblock', array($this->parentNode, 'getBlock'));
		}
		if ($this->readyBlocks >= $this->numBlocks) {
			$this->execute();
		}
		$this->req->onSleep();
	}

	public function getBlock($block) {
		$block['tag']        = (string)new \MongoId;
		$this->addedBlocks[] = $block;
		if (isset($block['name'])) {
			$this->addedBlocksNames[] = $block['name'];
		}
		++$this->numBlocks;
		return $block['tag'];
	}

	public function execute() {
		$this->ready();
	}

	public function onReadyBlock($obj) {
		if ($this->readyBlocks < $this->numBlocks) {
			return;
		}
		foreach ($this->inner as $k => $obj) {
			$this->html = str_replace($obj->tag, $obj->html, $this->html);
			unset($this->inner[$k]);
		}
		$this->execute();
	}

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
	}
}
