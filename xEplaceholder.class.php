<?php
/* Класс placeholder
  
 */
class xEplaceholder {

	public $req;
	public $name;
	public $blocks;
	public $readyBlocks = 0;
	public $numBlocks = 0;
	public $html = '';
	public $_nid;
	public function __construct($req,$ph) {
		
		$this->req = $req;
		$this->req->placeholders[] = $this;
		end($this->req->placeholders);
		$this->_nid = key($this->req->placeholders);
		foreach ($ph as $key => $value) {
			if ($key === 'blocks') {
				foreach ($value as $block) {
					if (!class_exists($class = 'xEmod'.$block['mod'])) {
						$class = 'xEmodText';
					}
					$obj = new $class($this,$block);
					++$this->numBlocks;
				}
				continue;
			}
			$this->{$key} = $value;
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
		
		$attrs = ' class=".'.htmlspecialchars($this->name,ENT_QUOTES).(isset($this->classes)?' '.$this->classes:'').'"';
		if (isset($this->id)) {
			$attrs .= ' id="'.htmlspecialchars($this->id,ENT_QUOTES).'"';
		}
		$this->req->html = str_replace($this->tag,'<div'.$attrs.'>'.$this->html.'</div>',$this->req->html);
		
	}
}
