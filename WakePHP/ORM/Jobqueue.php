<?php
namespace WakePHP\ORM;

use PHPDaemon\Core\Daemon;
use PHPDaemon\Core\Debug;
use PHPDaemon\Clients\Mongo\MongoId;
use WakePHP\ORM\Generic;

/**
 * Jobqueue
 */
class Jobqueue extends Generic {

	protected $jobqueue;
	protected $jobs;

	public function getCollection() {
		return $this->jobqueue;
	}
	public function init() {
		$this->jobs = $this->appInstance->db->{$this->appInstance->dbname . '.jobs'};
		$this->jobs->ensureIndex(['status' => 1]);
		$this->jobs->ensureIndex(['priority' => -1]);
		$this->jobs->ensureIndex(['atmostonce' => 1]);
		$this->jobqueue = $this->appInstance->db->{$this->appInstance->dbname . '.jobqueue'};
	}
	public function update($cond, $doc, $multi = false, $cb = null) {
		return $this->jobs->update($cond, $doc, $multi, $cb);
	}
	public function updateProgress($id, $progress, $status, $cb = null) {
		if (is_string($id)) {
			$id = new MongoId($id);
		}
		return $this->jobs->update(['_id' => $id], ['$set' => ['progress' => (float) $progress, 'status' => $status]], false, $cb);
	}
	public function getJobInfo($id, $accesskey, $cb) {
		if (is_string($id)) {
			$id = new MongoId($id);
		}
		$this->jobs->findOne($cb, ['where' => $accesskey !== null ? ['_id' => $id, 'accesskey' => (string) $accesskey] : ['_id' => $id]]);
	}
	public function getJobResult($id, $cb) {
		if (is_string($id)) {
			$id = new \MongoId($id);
		}
		$this->appInstance->jobresults->findOne($cb, ['where' => ['_id' => $id]]);
	}
	public function push($type, $args, $ts, $add, $cb = null, $ins = false) {
		$doc = [
			'type'     => $type,
			'args'     => $args,
			'status'   => 'v',
			'ts'       => $ts,
			'progress' => (float) 0,
		] + $add;
		if (isset($add['atmostonce']) && !$ins) {
			$this->jobs->upsertOne(
				['atmostonce' => $add['atmostonce']],
				[
					'$setOnInsert' => $doc,
					'$addToSet' => ['instance' => $this->appInstance->ipcId],
				], function($lastError) use ($type, $args, $ts, $add, $cb) {
				if (!$lastError['updatedExisting']) {
					$this->jobqueue->insert(['_id' => $lastError['upserted']]);
				}
			});
			return;
		}
		$id = new \MongoId;
		return $this->jobs->insert([
			'_id'	   => $id,
			'instance' => [$this->appInstance->ipcId],
		] + $add, function ($lastError) use ($type, $args, $ts, $add, $cb, $id) {
			$this->jobqueue->insert(['_id' => $id]);
		});
	}
}
