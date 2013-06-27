<?php
namespace WakePHP\Components\MUChat;

class AntifloodPlugin {
	use \PHPDaemon\Traits\ClassWatchdog;
	use \PHPDaemon\Traits\StaticObjectWatchdog;
	public function onMessage() {
	}
}