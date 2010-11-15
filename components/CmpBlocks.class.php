<?php

/**
 * Blocks component
 */
class CmpBlocks extends Component {
	
	public function saveBlockController() {
	
		$this->req->appInstance->blocks->saveBlock(array(
				'_id' => new MongoId($id = Request::getString($_REQUEST['id'])),
				'template' => Request::getString($_REQUEST['template']),
		));
		$this->req->setResult(array(
			'id' => $id
		));

	}
	public function getBlockSourceController() {
		
		$req = $this->req;
		$this->req->appInstance->blocks->getBlockById($id = Request::getString($_REQUEST['id']),function ($block) use ($req, $id) {

			if (!$block) {
				$block = array(
					'_id' => $id,
					'error' => 'Block not found.'
				);
			}
			else {
				unset($block['templatePHP']);
				unset($block['templateBC']);
				$block['_id'] = (string) $block['_id'];
			}
			$req->setResult($block);
		});
	}
}
