<?php

class ModACP extends Block {

	public function init() {
		if ($this->req->subPath === '/saveBlock') {
			return;
		}
		parent::init();
	}
	
	public function execute() {
		if ($this->req->subPath === '/saveBlock') {
		
				$this->req->appInstance->blocks->saveBlock(array(
					'_id' => new MongoId($id = Request::getString($_REQUEST['id'])),
					'template' => Request::getString($_REQUEST['template']),
				));
				$this->html = json_encode(array('id' => $id));
				$this->ready();
				
		}
		elseif ($this->req->subPath === '/getBlockSource') {
		
				$req = $this;
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
					$req->html = json_encode($block);
					$req->ready();
					
				});
				
		}		
		else {
				$this->ready();
		}
	}

}
