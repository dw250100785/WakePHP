<?php
namespace WakePHP\Components;

use PHPDaemon\Config\Entry;
use PHPDaemon\Request\Generic as Request;

/**
 * CAPTCHA component
 */
class CAPTCHA extends AsyncServer
{

	/**
	 * Function to get default config options from application
	 * Override to set your own
	 * @return array|bool
	 */
	protected function getConfigDefaults()
	{
		return array(
			'privatekey' => new ConfigEntry(''),
		);
	}

	public $req;
	public $appInstance;

	public function __construct($req)
	{
		$this->req         = $req;
		$this->appInstance = $req->appInstance;
	}

	public static function checkJob()
	{
		return function ($jobname, $job)
		{
			$job->req->components->CAPTCHA->validate(function ($captchaOK, $msg) use ($jobname, $job)
			{

				$errors = array();
				if (!$captchaOK)
				{
					if ($msg==='incorrect-captcha-sol')
					{
						$errors['captcha'] = 'Incorrect CAPTCHA solution.';
					}
					else
					{
						$errors['captcha'] = 'Unknown error.';
						$job->req->appInstance->log('CmpCaPTCHA: error: '.$msg);
					}
				}

				$job->setResult($jobname, $errors);
			});
		};
	}

	/**
	 * Establishes connection
	 * @param string $addr Address
	 * @return integer Connection's ID
	 */
	public function getConnection($addr)
	{
		if (isset($this->servConn[$addr]))
		{
			foreach ($this->servConn[$addr] as &$c)
			{
				return $c;
			}
		}
		else
		{
			$this->servConn[$addr] = array();
		}

		$e = explode(':', $addr);

		if (!isset($e[1]))
		{
			$e[1] = 80;
		}
		$connId = $this->connectTo($e[0], (int)$e[1]);

		$this->sessions[$connId]        = new    CAPTCHASession($connId, $this);
		$this->sessions[$connId]->addr  = $addr;
		$this->servConn[$addr][$connId] = $connId;

		return $connId;
	}

	public function validate($cb)
	{

		$connId = $this->getConnection('www.google.com');
		$this->sessions[$connId]->validate($cb);

	}
}

