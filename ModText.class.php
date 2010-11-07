<?php

class ModText extends Module {

	public function execute() {
		foreach ($this->inner as $block) {
			$this->html .= $block;
		}
		
		$this->ready();
	}

}
