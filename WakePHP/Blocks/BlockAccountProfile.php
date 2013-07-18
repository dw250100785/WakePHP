<?php
namespace WakePHP\Blocks;

use PHPDaemon\Core\ComplexJob;

class BlockAccountProfile extends Block {
	public function init() {

		$block = $this;
		$this->req->components->Account->onAuth(function ($result) use ($block) {

			$req = $block->req;

			if (!$req->account['logged']) {
				$req->redirectTo('/' . $req->locale . '/account/login?backurl=' . urlencode($req->attrs->server['REQUEST_URI']));
				$req->finish();
				return;
			}

			$job = $req->job = new ComplexJob(function ($job) use ($block) {
				$block->tplvars = $job->results;
				$job->block->runTemplate();
			});

			$job->req   = $req;
			$job->block = $block;

			/*$job('couponsNumber', function($jobname, $job) {
				$job->req->appInstance->coupons->countCoupons(function($result) use ($job, $jobname) {
          $job->setResult($jobname, $result['n']);
          
				}, array(
					'where' => array('account' => $job->req->account['_id'])
				));
			});*/

			$job();

		});
	}
}
