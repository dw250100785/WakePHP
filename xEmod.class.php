<?php
class xEmod {

	public $placeholder;
	public $html;
	public $ready = false;
		
	public function __construct($placeholder, $attrs) {
		$this->placeholder = $placeholder;

		$this->placeholder->blocks[] = $this;	
		end($this->placeholder->blocks);
		$this->id = key($this->placeholder->blocks);

		foreach ($attrs as $k => $v) {
			$this->{$k} = $v;
		}
		
		$this->execute();
	}
	public function execute() {
		$this->ready();
	}
	public function ready() {
		if ($this->ready) {
			return;
		}
		$this->ready = true;
		$this->placeholder->onReadyBlock($this->id);
	}
}
