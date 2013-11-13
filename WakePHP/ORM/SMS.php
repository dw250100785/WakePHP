<?php
namespace WakePHP\ORM;

use PHPDaemon\Core\Daemon;
use PHPDaemon\Core\Debug;
use PHPDaemon\Utils\Crypt;
use WakePHP\ORM\Generic;
use WakePHP\Objects\SMS as Objects;

/**
 * SMS
 */
class SMS extends Generic {

	public $messages;
	public function init() {
		Objects\Message::ormInit($this);
	}
	
}
