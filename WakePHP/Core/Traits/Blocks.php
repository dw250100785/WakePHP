<?php
namespace WakePHP\Core\Traits;
use PHPDaemon\Core\Daemon;
use PHPDaemon\Core\Debug;

/**
 * Blocks
 *
 * @package Core
 *
 * @author  Zorin Vasily <maintainer@daemon.io>
 */

trait Blocks {
	
	/**
	 * @param Block $block
	 * @return bool
	 */
	public function getBlock($block) {
		if (!$this->appInstance->backendClient) {
			return false;
		}
		if ($this->upstream instanceof BackendServerConnection) {
			return false;
		}

		if (ClassFinder::getClassBasename($block) === 'Block') {
			return false;
		}

		/**
		 * @param BackendClientConnection $conn
		 */
		$fc = function ($conn) use ($block) {
			if (!$conn->connected) {
				// fail
				return;
			}
			if (!$this->backendClientConn) {
				$this->backendClientConn = $conn;
				$conn->beginRequest($this);
			}
			$conn->getBlock($this->rid, $block);
			if ($this->backendClientCbs !== null) {
				$this->backendClientCbs->executeAll($conn);
				$this->backendClientCbs = null;
			}
		};
		if ($this->backendClientConn) {
			$this->backendClientConn->onConnected($fc);
		}
		else {
			if ($this->backendClientInited) {
				if ($this->backendClientCbs === null) {
					$this->backendClientCbs = new StackCallbacks();
				}
				$this->backendClientCbs->push($fc);
			}
			else {
				$this->appInstance->backendClient->getConnection($fc);
				$this->backendClientInited = true;
			}
		}
		return true;
	}

	/**
	 * @param $obj
	 */
	public function onReadyBlock($obj) {
		$this->html = str_replace($obj->tag, $obj->html, $this->html);
		unset($this->inner[$obj->_nid]);
		$this->wakeup();
	}

	
	/**
	 * @param array $block
	 */
	public function addBlock($block) {
		if ((!isset($block['type'])) || (!class_exists($class = '\\WakePHP\\Blocks\\Block' . $block['type']))) {
			$class = '\\WakePHP\\Blocks\\Block';
		}
		$block['tag']    = (string)new \MongoId;
		$block['nowrap'] = true;
		$this->html .= $block['tag'];
		new $class($block, $this);
	}

	/**
	 * @param $page
	 */
	public function loadPage($page) {

		++$this->jobDone;

		if (!$page) {
			++$this->jobTotal;
			try {
				$this->header('404 Not Found');
			} catch (RequestHeadersAlreadySent $e) {
			}
			$this->appInstance->blocks->getBlock(array(
													 'theme' => $this->theme,
													 'path'  => '/404',
												 ), array($this, 'loadErrorPage'));
			return;
		}
		$this->addBlock($page);
	}

	/**
	 * @param $page
	 */
	public function loadErrorPage($page) {

		++$this->jobDone;

		if (!$page) {
			$this->html = 'Unable to load error-page.';
			$this->wakeup();
			return;
		}

		$this->addBlock($page);

	}

}
