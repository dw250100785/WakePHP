<?php
class ModJSload extends Module {

	public $libs = array(
		'jquery/1.4.3/jquery',
		'jqueryui/1.8.6/jquery-ui',
	);

	public $min = false;
	
	public $nowrap = true;
	
	public function execute() {
		foreach ($this->libs as $lib) {
			$this->html .= '<script src="https://ajax.googleapis.com/ajax/libs/' . $lib . ($this->min ? '.min' : '') . '.js"></script>' . "\n";
		}

		$this->html .= '<script src="/js/CP.js" type="text/javascript"></script>' . "\n";
		$this->html .= '<script src="/js/jquery.keyboard.js" type="text/javascript"></script>' . "\n";

		$this->ready();
	}

}
