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
		$this->jobs->ensureIndex(['worker' => 1]);
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
	public function push($type, $args, $ts, $add, $cb = null) {
		$doc = [
			'type'     => $type,
			'args'     => $args,
			'status'   => 'v',
			'ts'       => $ts,
			'progress' => (float) 0,
		] + $add;
		if (isset($add['atmostonce'])) {
			$this->jobs->upsertOne(
				['atmostonce' => $add['atmostonce']],
				[
					'$setOnInsert' => $doc,
					'$addToSet' => [
						'instance' => $this->appInstance->ipcId
					],
					'$push' => ['trickyId' => $trickyId = new \MongoId],
				], function($lastError) use ($cb, &$trickyId) {
					if (!$lastError['updatedExisting']) {
						$this->jobqueue->insert(['_id' => $lastError['upserted'], 'ts' => microtime(true)]);
						$cb(MongoId::import($lastError['upserted']));
					}
					else {
						$this->jobs->findOne(function($item) use ($trickyId, $cb) {
							$cb(MongoId::import($item['_id']));
							$this->jobs->updateOne(
								['_id' => $item['_id']],
								['$pull' => ['trickyId' => $trickyId]]
							);
						}, ['where' => ['trickyId' => $trickyId]]);
					}
				}
			);
			return;
		}
		$doc['_id'] = $id = new MongoId;
		$doc['instance'] = [$this->appInstance->ipcId];
		$this->jobs->insert($doc, function ($lastError) use (&$id, $cb) {
			$this->jobqueue->insert(['_id' => $id, 'ts' => microtime(true)]);
			if ($cb !== null) {
				call_user_func($cb, $id);
			}
		});
		return $id;
	}
}
