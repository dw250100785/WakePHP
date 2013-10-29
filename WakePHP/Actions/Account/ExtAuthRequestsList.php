<?php
namespace WakePHP\Actions\Account;

use WakePHP\Actions\Generic;
use PHPDaemon\Request\Generic as Request;

/**
 * Class ExtAuthRequestsList
 * @package WakePHP\Actions\Account
 * @dynamic_fields
 */
class ExtAuthRequestsList extends Generic {

	public function perform() {
		$this->cmp->onAuth(function () {
			if (!$this->req->account['logged']) {
				$this->req->setResult([]);
				return;
			}
			$user_id = $this->req->account['_id'];
			$limit   = Request::getInteger($_REQUEST['limit']);
			$offset  = Request::getInteger($_REQUEST['offset']);
			if ($limit < 1) {
				$limit = 100;
			}
			if ($offset < 0) {
				$offset = 0;
			}
			$this->appInstance->externalAuthTokens->findWaiting($user_id, $limit, $offset, 'ctime,_id,ip,useragent,intToken', function ($cursor) {
				$result = [];
				foreach ($cursor->items as $item) {
					$item['id'] = (string)$item['_id'];
					$result[]   = $item;
				}
				$this->req->setResult($result);
				$cursor->destroy();
			});
		});
	}
}
