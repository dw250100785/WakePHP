<?php
namespace WakePHP\Blocks;

use PHPDaemon\Core\ComplexJob;

class BlockAccountProfile extends Block {
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


			/*$job('couponsNumber', function($jobname, $job) {
				$this->req->appInstance->coupons->countCoupons(function($result) use ($job, $jobname) {
          $job->setResult($jobname, $result['n']);
          
				}, array(
					'where' => array('account' => $this->req->account['_id'])
				));
			});*/

			$job();

		});
	}
}
