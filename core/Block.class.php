<?php

/**
 * Block instance class.	
 */
class Block implements ArrayAccess {

	public $html = '';
	
	public $readyBlocks = 0;
	public $numBlocks = 0;

	public $_nid;
	public $nowrap = false;

	public $parentNode;
	
	public $inner = array();
	
	public $req;
	
	public $ready = false;
	
	public $name;
	
	public $addedBlocks = array();
	public $addedBlocksNames = array();
	
	public function offsetSet($offset, $value) {}
	
	public function offsetExists($offset) {
		return isset($this->{$offset});
	}
	public function offsetUnset($offset) {
	}
	public function offsetGet($offset) {
    return $this->{$offset};
  }  
    
	public function __construct($attrs,	$parentNode) {
		$this->parentNode = $parentNode;
		$this->req = $this->parentNode->req;
		
		$this->parentNode->inner[] = $this;	
		
		end($this->parentNode->inner);
		$this->_nid = key($this->parentNode->inner);
	
		
		foreach ($attrs as $key => $value) {
			$this->{$key} = $value;
		}

		$this->init();
	}
	public function init() {
		$this->runTemplate();
	}
	public function assign() {
		// @TODO: local assignation?
		call_user_func_array(array($this->req->tpl, 'assign'), func_get_args());
	}
	public function runTemplate() {
		$this->req->onWakeup();
		if (isset($this->template)) {
			$this->assign('block',	$this);
			$this->req->tpl->register_function('getblock',array($this,'getBlock'));
			$this->html = $this->req->tpl->PHPtemplateFetch($this->templatePHP);
					
			++$this->req->jobTotal;
			$node = $this;
			$this->req->appInstance->blocks->blocks->find(
				function($cursor) use ($node) {
		
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
				
						foreach ($node->addedBlocks as $block) {
							if (isset($block['name']) && isset($dbprops[$block['name']])) {
								$block = array_merge($block,$dbprops[$block['name']]);
							}
							if ((!isset($block['type'])) || (!class_exists($class = 'Block'.$block['type']))) {
								$class = 'Block';
							}
							new $class($block,$node);
						}
						unset($node->addedBlocks);
				
						++$node->req->jobDone;
						$node->req->wakeup();
					}
				}, array(
					'where' => array('name' => array('$in' => array_unique($this->addedBlocksNames)))
				)
			);
			unset($this->addedBlocksNames);
			$this->req->tpl->register_function('getblock',array($this->parentNode,'getBlock'));
		}
		if ($this->readyBlocks >= $this->numBlocks) {
			$this->execute();
		}
		$this->req->onSleep();
	}
	public function getBlock($block) {
		$block['tag'] = (string) new MongoId;
		$this->addedBlocks[] = $block;
		$this->addedBlocksNames[] = $block;
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
			$this->html = str_replace($obj->tag,$obj->html,$this->html);
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
				(isset($this->classes)?' ' . $this->classes : '') . '"';

			if (isset($this->id)) {
				$attrs .= ' id="' . htmlspecialchars($this->id,ENT_QUOTES) . '"';
			}
			elseif (isset($this->_id)) {
				$attrs .= ' id="' . htmlspecialchars($this->_id,ENT_QUOTES) . '"';
			}

			$this->html = '<div' . $attrs . '>' . $this->html . '</div>';
		}
		++$this->parentNode->readyBlocks;
		$this->parentNode->onReadyBlock($this);
	}

}