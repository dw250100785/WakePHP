<?php
namespace WakePHP\Jobs;
abstract class Generic {
      protected $parent;
      public function __construct($job, $parent) {
            foreach ($job as $k => $v) {
                  $this->{$k} => $v;
            }
            $this->parent = $parent;
      }
      abstract function run();
      public function sendResult($result) {
            $status = $result !== false ? 's' : 'f';
            $this->parent->jobqueue->update(
                  ['_id' => $job['_id']],
                  ['$set' => ['status' => $status]]
            );
            $this->parent->jobresults->insert([
                                                        '_id'      => $job['_id'],
                                                        'ts'       => microtime(true),
                                                        'instance' => $job['instance'],
                                                        'status'   => $status,
                                                        'result'   => $result
                                                  ]);

            $this->parent->jobresults->insert([
                                                        'jobId'    => $job['_id'],
                                                        'ts'       => microtime(true),
                                                        'instance' => $job['instance'],
                                                        'status'   => '',
                                                        'result'   => $result
                                                  ]);
      }
}