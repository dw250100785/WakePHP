<?php

/**
 * Upload component
 */
class CmpUpload extends Component {
	
	public function checkReferer() {
		if ($this->req->controller === 'UploadFile') {
			return true;
		}
		return $this->req->checkDomainMatch();
	}
	public function	UploadFileController() {
		$req = $this->req;
		$handler = Request::getString($req->attrs->request['handler']);
		$formatType = Request::getString($req->attrs->request['formatType']);
		$errors = array();
		if ($handler === '') {
			$errors['handler'] = 'Empty name of handler.';
		}
		else {
			list ($cmpName, $handlerName) = explode('/', $handler . '/');
			$method = $handlerName.'UploadHandler';
			$cmp = $req->components->{$cmpName};
			if (!$cmp) {
				$errors['handler'] = 'Undefined component.';
			}
			elseif (method_exists($cmp, $method)) {
				$cmp->$method();
			}
			else {
				$errors['handler'] = 'Undefined handler.';
			}
		}
		if ($formatType === '') {
			$errors['formatType'] = 'Empty name of format type.';
		}
		if (sizeof($errors) > 0) {
			$req->setResult(array('success' => false, 'errors' => $errors));
		}
	}		
}
