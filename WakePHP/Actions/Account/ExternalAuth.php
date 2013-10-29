<?php
namespace WakePHP\Actions\Account;

use WakePHP\Actions\Generic;
use PHPDaemon\Request\Generic as Request;
use WakePHP\ExternalAuthAgents\Generic as ExternalAuthAgents;

/**
 * Class ExternalAuth
 * @package WakePHP\Actions\Account
 * @dynamic_fields
 */
class ExternalAuth extends Generic {

	public function perform() {
		if (!($AuthAgent = ExternalAuthAgents::getAgent(Request::getString($this->req->attrs->get['agent']), $this))) {
			$this->req->setResult(['error' => true, 'errmsg' => 'Unrecognized external auth agent']);
			return;
		}
		if (isset($_GET['backurl'])) {
			$AuthAgent->setBackUrl(Request::getString($_GET['backurl']));
		}
		$AuthAgent->auth();
	}
}
