<?php
namespace WakePHP\Core\Traits;
use PHPDaemon\Core\Daemon;
use PHPDaemon\Core\Debug;

/**
 * URLToolkit
 *
 * @package Core
 *
 * @author  Zorin Vasily <maintainer@daemon.io>
 */

trait URLToolkit {
	
	/**
	 * @return string
	 */
	public function getBaseUrl() {
		return 'http' . ((isset($this->attrs->server['HTTPS']) && $this->attrs->server['HTTPS'] === 'on') ? 's' : '') .
		 '://' . $this->attrs->server['HTTP_HOST'];
	}

	/**
	 * @return string
	 */
	public function getBackUrl($backUrl) {
		if ($backUrl !== null) {
			$domain = parse_url($backUrl, PHP_URL_HOST);
			if (!$this->checkDomainMatch($domain)) {
				return $this->getBaseUrl();
			}
			return $backUrl;
		} else {
			return $this->getBaseUrl();
		}
	}

	/**
	 * @param string $domain
	 * @param string $pattern
	 * @return bool
	 */
	public function checkDomainMatch($domain = null, $pattern = null) {
		if ($domain === null) {
			$domain = parse_url(static::getString($this->attrs->server['HTTP_REFERER']), PHP_URL_HOST);
		}
		if ($pattern === null) {
			$pattern = $this->appInstance->config->cookiedomain->value;
		}
		foreach (explode(', ', $pattern) as $part) {
			if (substr($part, 0, 1) === '.') {
				if ('.' . ltrim(substr($domain, -strlen($part)), '.') === $part) {
					return true;
				}
			}
			else {
				if ($domain === $part) {
					return true;
				}
			}
		}
		return false;
	}
}
