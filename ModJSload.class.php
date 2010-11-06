<?php
class ModJSload extends Module implements NoticeableModule {

	public $libs = array(
	 'jquery/1.4.3/jquery',
	 'jquery/1.8.6/jquery-ui',
	);

	public $min = false;
	
	public function checkDependencies() {
		if (sizeof($this->placeholder->req->placeholders) == 1) {
			$this->ready();
		}
	}
	
	public function onReadyAnotherPlaceholder($name) {
		$this->checkDependencies();
	}

	public function execute() {

		foreach ($this->libs as $lib) {
		 $this->html .= '<script src="https://ajax.googleapis.com/ajax/libs/'.$lib.($this->min?'.min':'').'.js"></script>'."\n";
		}
		
		$this->checkDependencies();

	}
}
