<?php
namespace WakePHP\ORM;

use PHPDaemon\Core\Daemon;
use PHPDaemon\Core\Debug;
use PHPDaemon\Clients\Mongo\MongoId;
use WakePHP\ORM\Generic;
use WakePHP\Objects\Jobqueue as Objects;
/**
 * Jobqueue
 */
class Jobqueue extends Generic {

	public $jobqueue;
	public $jobs;

	public function getCollection() {
		return $this->jobqueue;
	}
	public function init() {
		Objects\Job::ormInit($this);
	}
	public function update($cond, $doc, $multi = false, $cb = null) {
		return $this->jobs->update($cond, $doc, $multi, $cb);
	}
	public function getJobInfo($id, $accesskey, $cb) {
		$id = MongoId::import($id);
		$this->getJob($accesskey !== null ? ['_id' => $id, 'accesskey' => (string) $accesskey] : ['_id' => $id], $cb);
	}
	public function getJobResult($id, $cb) {
		$this->getJobById($id, $cb);
	}
	public function push($type, $args, $ts, $add, $cb = null) {
		$this->newJob([
			'type'     => $type,
			'args'     => $args,
			'status'   => 'v',
			'ts'       => $ts,
			'progress' => (float) 0,
		] + $add, $cb);
	}
}
