<?php
namespace WakePHP\Core;

use PHPDaemon\Config\Entry\ConfigFile;
use PHPDaemon\Core\ClassFinder;
use PHPDaemon\Core\DeferredEvent;
use PHPDaemon\Traits\DeferredEventHandlers;

/**
 * Component
 * @method onSessionRead(callable $cb)
 * @method startSession
 */
class Component {
	use DeferredEventHandlers;
	use \PHPDaemon\Traits\StaticObjectWatchdog;

	/** @var Request */
	public $req;
	/** @var WakePHP */
	public $appInstance;
	/**
	 * @var ConfigFile
	 */
	public $config;

	/**
	 * @param Request $req
	 */
	public function __construct($req) {
		$this->req         = $req;
		$this->appInstance = $req->appInstance;
		$my_class          = ClassFinder::getClassBasename(get_class($this));
		$this->config      = isset($this->appInstance->config->{$my_class}) ? $this->appInstance->config->{$my_class} : null;
		$defaults          = $this->getConfigDefaults();
		if ($defaults) {
			$this->processDefaultConfig($defaults);
		}
		$this->init();
	}

	public function init() {
	}

	/**
	 * Function to get default config options from application
	 * Override to set your own
	 * @return array|bool
	 */
	protected function getConfigDefaults() {
		return false;
	}

	/**
	 * @return bool
	 */
	public function checkReferer() {
		return $this->req->checkDomainMatch();
	}

	/**
	 * Process default config
	 * @param array {"setting": "value"}
	 * @return void
	 */
	private function processDefaultConfig($settings = array()) {
		foreach ($settings as $k => $v) {
			$k = strtolower(str_replace('-', '', $k));

			if (!isset($this->config->{$k})) {
				if (is_scalar($v)) {
					$this->config->{$k} = new \PHPDaemon\Config\Entry\Generic($v);
				}
				else {
					$this->config->{$k} = $v;
				}
			}
			else {
				$current = $this->config->{$k};
				if (is_scalar($v)) {
					$this->config->{$k} = new \PHPDaemon\Config\Entry\Generic($v);
				}
				else {
					$this->config->{$k} = $v;
				}

				$this->config->{$k}->setHumanValue($current->value);
				$this->config->{$k}->source   = $current->source;
				$this->config->{$k}->revision = $current->revision;
			}
		}
	}

	public function cleanup() {
		$this->cleanupDeferredEventHandlers();
	}
}
