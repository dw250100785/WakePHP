<?php
namespace WakePHP\ORM;

use WakePHP\Core\ORM;

/**
 * Jobqueue
 */
class Jobqueue extends ORM {

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
		$this->jobqueue->findOne($accesskey !== null ? ['_id' => $id, 'accesskey' => $accesskey] : ['_id' => $id], $cb);
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
