<?php
class ModPagetook extends Module implements NoticeableModule {

	public function checkDependencies() {
		if (sizeof($this->placeholder->req->placeholders) == 1) {
			$this->html = '<div>Page took: '.round(microtime(true) - $this->placeholder->req->startTime,6).'</div>';
			$this->ready();
		}
	}
	
	public function onReadyAnotherPlaceholder($name) {
		$this->checkDependencies();
	}

	public function execute() {
		$this->checkDependencies();
	}
}
