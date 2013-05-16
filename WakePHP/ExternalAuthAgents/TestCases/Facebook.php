<?php
namespace WakePHP\ExternalAuthAgents\TestCases;

class Facebook extends \WakePHP\Core\TestCase {
	public function testAuth() {
		$Request  = $this->getRequestMock();
		$Facebook = new \WakePHP\ExternalAuthAgents\Facebook($Request->components->account);
		$Facebook->auth();
		$this->assertArrayHasKey('Location', $Request->headers_list());
	}
}
