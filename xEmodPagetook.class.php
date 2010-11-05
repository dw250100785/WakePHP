<?php
class xEmodPagetook extends xEmod {

	public function execute() {

		$this->html = '<div>Page took: '.round(microtime(true) - $this->placeholder->req->startTime,6).'</div>';
		$this->ready();
		
	}
}
