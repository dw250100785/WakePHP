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
		
		Daemon::log(__METHOD__);
		
		foreach ($attrs as $key => $value) {
			$this->{$key} = $value;
		}
				
		$this->req->tpl->assign('block',	$this);
		if (isset($this->template)) {
			$this->html = $this->req->templateFetch($this->template);
			$this->req->appInstance->blocks->parse($this);
		}
		if ($this->readyBlocks >= $this->numBlocks) {
			$this->execute();
		}
	}
	public function execute() {
		$this->ready();
	}
	
	public function onReadyBlock($obj) {
		if ($this->readyBlocks < $this->numBlocks) {
			return;
		}
		Daemon::log(sizeof($this->inner));
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