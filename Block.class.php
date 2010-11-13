<?php

/**
 * Block instance class.	
 */
class Block {

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
	
	public function __construct($attrs,	$parentNode) {
		$this->parentNode = $parentNode;
		$this->req = $this->parentNode->req;
		
		$this->parentNode->inner[] = $this;	
		
		end($this->parentNode->inner);
		$this->_nid = key($this->parentNode->inner);
	
		
		foreach ($attrs as $key => $value) {
			$this->{$key} = $value;
		}
				
		$this->req->tpl->assign('block',	$this);
		$this->init();
		
		if ($this->readyBlocks >= $this->numBlocks) {
			$this->execute();
		}
	}
	public function init() {
		if (isset($this->template)) {
			$this->html = $this->req->templateFetch($this->template);
			$this->parseHTML($this);
		}
	}
	public function parseHTML() {
		$blocks = array();
		$names = array();
		
		$parser = xml_parser_create();
		xml_parser_set_option($parser, XML_OPTION_CASE_FOLDING, 0);
		
		$node = $this;
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
		$parse = xml_parse($parser,$this->html);
		xml_parser_free($parser);
		
		$names = array_unique($names);
		
		++$this->req->jobTotal;
		$this->req->appInstance->blocks->blocks->find(
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