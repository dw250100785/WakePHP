<?php
namespace WakePHP\Blocks;

class BlockGenericLoggedDep extends Block
{

	public function init()
	{
		$this->req->components->Account->onAuth(function ()
		{
			if (!$this->req->account['logged'])
			{
				$this->req->redirectTo([$this->req->locale.'/account/login', 'backurl' => $this->req->attrs->server['REQUEST_URI']]));
			}
			else
			{
				$this->runTemplate();
			}
		});
	}

}
