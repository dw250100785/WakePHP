<?php
namespace WakePHP\Blocks;

use WakePHP\Core\Request;
use PHPDaemon\Core\ComplexJob;

class BlockAccountExtAuth extends Block {
	public function init() {
		$this->req->components->Account->onAuth(function ($result) {
			if (!$this->req->account['logged'])	{
				$this->req->redirectToLogin();
				return;
			}

			$job = $this->req->job = new ComplexJob(function ($job) {
				$this->tplvars = $job->results;
				$this->runTemplate();
			});

			$job('currentTokenId', function($jobname, $job) {
				$this->req->appInstance->externalAuthTokens->findByIntToken(Request::getString($_REQUEST['i']), function($token) use ($job, $jobname) {
					if (!$token) {
						$job->setResult($jobname, null);
						return;
					}
					if (isset($token['uid']) && ($token['uid'] != $this->req->account['_id'])) {
						$job->setResult($jobname, null);
						return;
					}
					if (!isset($token['uid'])) {
						$token['uid'] = $this->req->account['_id'];
						$this->req->appInstance->externalAuthTokens->save([
							'extTokenHash' => $token['extTokenHash'],
							'uid' => $token['uid'],
						]);
					}
          			$job->setResult($jobname, (string) $token['_id']);
				});
			});

			$job();

		});
	}
}
