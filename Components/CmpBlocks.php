<?php
namespace WakePHP\Components;

use PHPDaemon\Request;
use WakePHP\Core\Component;

/**
 * Blocks component
 */
class CmpBlocks extends Component {

	public function saveBlockController() {
		$this->appInstance->blocks->saveBlock(
			['_id'      => new \MongoId($id = Request::getString($this->req->attrs->request['id'])),
			 'template' => Request::getString($_REQUEST['template']),
			], true);
		$this->req->setResult(['id' => $id]);
	}

	public function checkRole($role) {
		static $roles = [
			'Webmaster' => array('Superusers', 'Webmasters'),
		];
		if (!isset($roles[$role])) {
			return false;
		}
		foreach ($roles[$role] as $group) {
			if (in_array($group, $this->req->account['aclgroups'], true)) {
				return true;
			}
		}
		return false;
	}

	public function getBlockSourceController() {

		$req = $this->req;
		$this->appInstance->blocks->getBlockById($id = Request::getString($this->req->attrs->request['id']), function ($block) use ($req, $id) {

			if (!$block) {
				$block = [
					'_id'   => $id,
					'error' => 'Block not found.'
				];
			}
			else {
				unset($block['templatePHP']);
				unset($block['templateBC']);
				$block['_id'] = (string)$block['_id'];
			}
			$req->setResult($block);
		});
	}
}
