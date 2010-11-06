<?php
/* ActiveNode instance class.
  
 */
class ActiveNode {

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
		++$this->parentNode->numBlocks;
		
		end($this->parentNode->inner);
		$this->_nid = key($this->parentNode->inner);
		
//Daemon::log(get_class($this).' - '.Debug::dump($attrs));
		foreach ($attrs as $key => $value) {
			if ($key == 'inner') {
				
				if (is_scalar($value)) {
					$value = array($value);
				}
				
				foreach ($value as $block) {
					if (is_scalar($block)) {
						$block = array(
						 'mod' => 'Text',
						 'html' => $block,
						);
					}
					if (!class_exists($class = 'Mod'.$block['mod'])) {
						$class = 'ModText';
					}
					new $class($block, $this);
				}
				continue;
			}
			$this->{$key} = $value;
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
		
		foreach ($this->inner as $k => $block) {
			
			$this->html .= $block->html;
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
			$attrs = ' class="placeholder '.htmlspecialchars($this->name,ENT_QUOTES).(isset($this->classes)?' '.$this->classes:'').'"';
			if (isset($this->id)) {
				$attrs .= ' id="'.htmlspecialchars($this->id,ENT_QUOTES).'"';
			}
			$this->html = '<div'.$attrs.'>'.$this->html.'</div>';
		}
		
		++$this->parentNode->readyBlocks;
		$this->parentNode->onReadyBlock($this);
		unset($this->parentNode->inner[$this->_nid]);
	}
}
