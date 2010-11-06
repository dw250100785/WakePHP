<?php
class ModText extends Module {

	public function execute() {

		$this->html = '<p>'.htmlspecialchars($this->text).'</p>';
		$this->ready();
		
	}
}
