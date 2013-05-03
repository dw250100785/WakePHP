<?php
namespace WakePHP\core;
/**
 * Component
 */
class Component {

	/** @var  WakePHPRequest */
	public $req;
	/** @var WakePHP */
	public $appInstance;
	public $config;
	public function __construct($req) {
		$this->req = $req;
		$this->appInstance = $req->appInstance;
		$this->config = isset($this->appInstance->config->{get_class($this)}) ? $this->appInstance->config->{get_class($this)} : null;
		$defaults = $this->getConfigDefaults();
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
	 * @return array|false
	 */
	protected function getConfigDefaults() {
		return false;
	}
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
			  if (is_scalar($v))	{
					$this->config->{$k} = new \Daemon_ConfigEntry($v);
				} else {
					$this->config->{$k} = $v;
				}
			} else {
				$current = $this->config->{$k};
			  if (is_scalar($v))	{
					$this->config->{$k} = new \Daemon_ConfigEntry($v);
				} else {
					$this->config->{$k} = $v;
				}
				
				$this->config->{$k}->setHumanValue($current->value);
				$this->config->{$k}->source = $current->source;
				$this->config->{$k}->revision = $current->revision;
			}
		}
	}
	public function __get($event) {
		if (!method_exists($this, $event.'Event')) {
			//throw new UndefinedEventCalledException('Undefined event called: ' . get_class($this). '->' . $event);
			return null;
		}
		$this->{$event} = new DeferredEvent($this->{$event.'Event'}());
		$this->{$event}->component = $this;
		return $this->{$event};
	}
	public function cleanup() {
		foreach ($this as $key => $property) {
			if ($property instanceof DeferredEvent) {
				$property->cleanup();
			}
			unset($this->{$key});
		}
	}
	public function __call($event,$args) {
		return call_user_func_array($this->{$event},$args);
	}
}
