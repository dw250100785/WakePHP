<?php
namespace WakePHP\Actions\Account;

use WakePHP\Actions\Generic;
use PHPDaemon\Request\Generic as Request;
use PHPDaemon\Core\ComplexJob;
use PHPDaemon\Core\Daemon;
use PHPDaemon\Core\Debug;
use PHPDaemon\Clients\Mongo\Cursor;
use WakePHP\Core\Request as WakePHPRequest;

/**
 * Class ManageAccounts
 * @package WakePHP\Actions\Account
 * @dynamic_fields
 */
class ManageAccounts extends Generic {

	public function perform() {
		$this->cmp->onAuth(function ($result) {
			if (!in_array('Superusers', $this->req->account['aclgroups'], true)) {
				$this->req->setResult(['success' => false, 'goLoginPage' => true]);
				return;
			}

			static $fields = [
				'email'     => 1,
				'username'  => 1,
				'regdate'   => 1,
				'ip'        => 1,
				'firstname' => 1,
				'lastname'  => 1,
				'location'  => 1,
				'aclgroups' => 1,
				'_id'       => 1,
			];
			$fieldNames = array_keys($fields);
			$field      = function ($n) use ($fieldNames) {
				if (!isset($fieldNames[$n])) {
					return null;
				}
				return $fieldNames[$n];
			};

			$action = Request::getString($_REQUEST['action']);
			if ($action === 'EditColumn') {
				$column = $field(Request::getInteger($_REQUEST['column']));
				if ($column === null) {
					$this->req->setResult(['success' => false, 'error' => 'Column not found.']);
					return;
				}

				/** @noinspection PhpIllegalArrayKeyTypeInspection */
				$this->req->appInstance->accounts
				->getAccount()->condSetId(Request::getString($_REQUEST['id']))
				->attr($column, $value = Request::getString($_REQUEST['value']))
				->save(function ($o) use ($value) {
					Daemon::log(Debug::dump($o->lastError()));
					if ($o->lastError(true)) {
						$this->req->setResult(['success' => true, 'value' => $value]);
					}
					else {
						$this->req->setResult(['success' => false, 'error' => 'Account not found.']);
					}
				});

				return;
			}

			$where   = [];
			$sort    = [];
			$sortDir = [];

			foreach ($_REQUEST as $k => $value) {
				list ($type, $index) = explode('_', $k . '_');
				if ($type === 'iSortCol') {
					/** @noinspection PhpIllegalArrayKeyTypeInspection */
					$sort[$field($value)] = Request::getString($_REQUEST['sSortDir_' . $index]) == 'asc' ? 1 : -1;
				}
			}
			unset($sort[null]);

			$offset = Request::getInteger($_REQUEST['iDisplayStart']);
			$limit  = Request::getInteger($_REQUEST['iDisplayLength']);

			$job = $this->req->job = new ComplexJob(function ($job) {

				$this->req->setResult([
					'success'              => true,
					'sEcho'                => (int)Request::getString($_REQUEST['sEcho']),
					'iTotalRecords'        => $job->results['countTotal'],
					'iTotalDisplayRecords' => $job->results['countFiltered'],
					'aaData'               => $job->results['find'],
				]);

			});

			$job('countTotal', function ($jobname, $job) {
				$this->req->appInstance->accounts->countAccount(function ($o, $n) use ($job, $jobname) {
					/** @var ComplexJob $job */
					$job->setResult($jobname, $n);
				});
			});

			$job('countFiltered', function ($jobname, $job) use ($where, $limit) {
				/** @var ComplexJob $job */
				/** @var WakePHPRequest $job->req */
				$this->req->appInstance->accounts->countAccount(function ($o, $n) use ($job, $jobname, $where) {
					/** @var ComplexJob $job */
					$job->setResult($jobname, $n);
				}, $where);
			});

			$job('find', function ($jobname, $job) use ($where, $sort, $fields, $fieldNames, $field, $offset, $limit) {
				$this->req->appInstance->accounts->findAccounts(function ($cursor) use ($jobname, $job, $fieldNames, $offset, $limit) {
					/** @var Cursor $cursor */
					/** @var ComplexJob $job */
					$accounts = [];
					foreach ($cursor as $item) {
						$account = [];
						foreach ($fieldNames as $k) {
							if (!isset($item[$k])) {
								$val = null;
							}
							else {
								$val = $item[$k];
								if ($k === 'regdate') {
									$val = $val != 0 ? date('r', $val) : '';
								}
								elseif ($k === '_id') {
									$val = (string)$val;
								}
								else {
									if ($k === 'aclgroups') {
										$val = (string)implode(', ', $val);
									}
									$val = htmlspecialchars($val);
								}
							}
							$account[] = $val;
						}
						$accounts[] = $account;
					}
					$cursor->destroy();
					$job->setResult($jobname, $accounts);
				}, [
					'fields' => $fields,
					'sort'   => $sort,
					'offset' => $offset,
					'limit'  => -abs($limit),
				]);

			});

			$job();

		});
	}
}
