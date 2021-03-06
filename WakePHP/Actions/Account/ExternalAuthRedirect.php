<?php
namespace WakePHP\Actions\Account;

use WakePHP\Actions\Generic;
use PHPDaemon\Request\Generic as Request;
use WakePHP\ExternalAuthAgents\Generic as ExternalAuthAgents;

/**
 * Class ExternalAuthRedirect
 * @package WakePHP\Actions\Account
 * @dynamic_fields
 */
class ExternalAuthRedirect extends Generic {

	public function perform() {
		if (!($AuthAgent = ExternalAuthAgents::getAgent(Request::getString($this->req->attrs->get['agent']), $this->cmp))) {
			$this->req->setResult(['error' => true, 'errmsg' => 'Unrecognized external auth agent']);
			return;
		}
		if (isset($_GET['backurl'])) {
			$AuthAgent->setBackUrl(Request::getString($_GET['backurl']));
		}
		$AuthAgent->redirect();
	}
}
