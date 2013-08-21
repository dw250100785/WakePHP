<?php
namespace WakePHP\ORM;

use WakePHP\ORM\Generic;

/**
 * Jobqueue
 */
class Jobqueue extends Generic {

	protected $jobqueue;

	public function init() {
		$this->jobqueue = $this->appInstance->db->{$this->appInstance->dbname . '.jobqueue'};
	}
	public function update($cond, $doc, $multi = false, $cb = null) {
		return $this->jobqueue->update($cond, $doc, $multi, $cb);
	}
	public function updateProgress($id, $progress, $status, $cb = null) {
		if (is_string($id)) {
			$id = new \MongoId($id);
		}
		return $this->jobqueue->update(['_id' => $id], ['$set' => ['progress' => (float) $progress, 'status' => $status]], false, $cb);
	}
	public function getJobInfo($id, $accesskey, $cb) {
		if (is_string($id)) {
			$id = new \MongoId($id);
		}
		$this->jobqueue->findOne($cb, ['where' => $accesskey !== null ? ['_id' => $id, 'accesskey' => (string) $accesskey] : ['_id' => $id]]);
	}
	public function getJobResult($id, $cb) {
		if (is_string($id)) {
			$id = new \MongoId($id);
		}
		$this->appInstance->jobresults->findOne($cb, ['where' => ['_id' => $id]]);
	}
	public function push($type, $args, $ts, $add, $cb = null) {
		return $this->jobqueue->insert([
			'type'     => $type,
			'args'     => $args,
			'status'   => 'v',
			'ts'       => $ts,
			'progress' => (float) 0,
			'instance' => $this->appInstance->ipcId,
		] + $add, $cb);
	}
}
