<?php

/**
 * Account component
 */
class CmpAccount extends Component {
	
	public function onAuthEvent() {
		
		return function($event) {
			Daemon::log('test!!');
			$event->setResult(array('ok'));
		};
	}
		
}
