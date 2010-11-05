<?php
class xEmodText extends xEmod {

	public function execute() {

		$this->html = '<p>'.htmlspecialchars($this->text).'</p>';
		$this->ready();
		
	}
}
