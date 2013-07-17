<?php
namespace WakePHP\Core;

use PHPDaemon\Core\DeferredEvent;

/**
 * Class DeferredEventCmp
 * @package WakePHP\Core
 */
class DeferredEventCmp extends DeferredEvent {
	use \PHPDaemon\Traits\StaticObjectWatchdog;

	public $parent;
}
