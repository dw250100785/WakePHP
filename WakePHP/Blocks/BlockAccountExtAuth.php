<?php
namespace WakePHP\Blocks;

use WakePHP\Core\Request;
use PHPDaemon\Core\ComplexJob;

class BlockAccountExtAuth extends Block {
	public function init() {
		$this->req->components->Account->onAuth(function ($result) {
			if (!$this->req->account['logged']) {
				$this->req->header('Location: /' . $this->req->locale . '/account/login?backurl=' . urlencode($_SERVER['REQUEST_URI']));
				$this->req->finish();
				return;
			}

			$job = $this->req->job = new ComplexJob(function ($job) {
				$this->tplvars = $job->results;
				$job->block->runTemplate();
			});

			$job('currentTokenId', function($jobname, $job) {
				$this->req->appInstance->externalAuthTokens->findByIntToken(Request::getString($_REQUEST['i']), function($token) use ($job, $jobname) {
					if (!$token) {
						$job->setResult($jobname, null);
						return;
					}
					if (isset($token['uid']) && ($token['uid'] != $req->account['_id'])) {
						$job->setResult($jobname, null);
						return;
					}
					if (!isset($token['uid'])) {
						$token['uid'] = $req->account['_id'];
						$this->req->appInstance->externalAuthTokens->save($token['extTokenHash'], [
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
