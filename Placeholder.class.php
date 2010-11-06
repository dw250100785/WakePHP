<?php
/* Placeholder instance class.
  
 */
class Placeholder {

	public $req;
	public $name;
	public $blocks;
	public $readyBlocks = 0;
	public $numBlocks = 0;
	public $html = '';
	public $_nid;
	public $nowrap = false;
	public $noticable = false;
	public function __construct($req,$ph) {
		
		$this->req = $req;
		$this->req->placeholders[] = $this;
		end($this->req->placeholders);
		$this->_nid = key($this->req->placeholders);
		foreach ($ph as $key => $value) {
			if ($key === 'blocks') {
				foreach ($value as $block) {
					if (!class_exists($class = 'Mod'.$block['mod'])) {
						$class = 'ModText';
					}
					$obj = new $class($this,$block);
					if (!$this->noticable && $obj instanceof NoticableModule) {
						$this->noticable = true;
						$this->req->noticeablePlaceholders[$this->_nid] = true;
					}
					++$this->numBlocks;
				}
				continue;
			}
			$this->{$key} = $value;
		}
		
	}
	public function onReadyAnotherPlaceholder($name) {
		foreach ($this->blocks as $block) {
			if ($block instanceof NoticableModule) {
				$block->onReadyAnotherPlaceholder($name);
			}
		}
	}
	public function onReadyBlock($id) {
		++$this->readyBlocks;
		
		if ($this->readyBlocks < $this->numBlocks) {
			return;
		}
		
		foreach ($this->blocks as $k => $block) {
			
			$this->html .= $block->html;
			unset($this->blocks[$k]);
			
		}
		unset($this->req->placeholders[$this->_nid]);
	}
	public function __destruct() {
		
		if ($this->noticable) {
			unset($this->req->noticeablePlaceholders[$this->_nid]);
		}
		foreach ($this->req->noticeablePlaceholders as $nid) {
			$this->req->placeholders[$nid]->onReadyAnotherPlaceholder($this->name);
		}
		
		if ($this->nowrap) {
			$this->req->html = str_replace($this->tag,$this->html,$this->req->html);
			return;
		}
		
		$attrs = ' class="placeholder '.htmlspecialchars($this->name,ENT_QUOTES).(isset($this->classes)?' '.$this->classes:'').'"';
		if (isset($this->id)) {
			$attrs .= ' id="'.htmlspecialchars($this->id,ENT_QUOTES).'"';
		}
		$this->req->html = str_replace($this->tag,'<div'.$attrs.'>'.$this->html.'</div>',$this->req->html);
		
	}
}
