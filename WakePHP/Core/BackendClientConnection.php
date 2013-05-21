<?php
namespace WakePHP\Core;

use PHPDaemon\Core\Daemon;
use PHPDaemon\Network\Connection;
use WakePHP\Blocks\Block;

class BackendClientConnection extends Connection
{

	public $reqCounter = 0;

	public function onPacket($p)
	{
		if (!is_array($p))
		{
			return;
		}
		if ($p['type']==='propertyUpdated')
		{
			$rid = $p['rid'];
			/** @q where is property `requests` declared?
			 * in Connection::__get only allowed fields are: 'connected', 'hostReal', 'host', 'port', 'finished', 'alive', 'freed', 'url'
			 */
			if (!isset($this->requests[$rid]))
			{
				Daemon::log(__METHOD__.': Undefined request #'.$rid);
				return;
			}
			$req               = $this->requests[$rid];
			$req->{$p['prop']} = $p['val'];
		}
		elseif ($p['type']==='readyBlock')
		{
			$rid = $p['rid'];
			if (!isset($this->requests[$rid]))
			{
				Daemon::log(__METHOD__.': Undefined request #'.$rid);
				return;
			}
			/** @var Request $req */
			$req = $this->requests[$rid];
			$bid = $p['bid'];
			if (!isset($req->queries[$bid]))
			{
				Daemon::log(__METHOD__.': >>>>> Undefined block #'.$rid.'-'.$bid.': '.json_encode($p));
				return;
			}
			$block = $req->queries[$bid];
			$block->runTemplate();
			unset($req->queries[$bid]);
			//Daemon::log(__METHOD__.': Unsetting block #'.$rid.'-'.$bid . ': '. json_encode($p));
		}
	}

	public function onFinish()
	{
		$this->queries = null;
	}

	/**
	 * @param Request $req
	 */
	public function beginRequest($req)
	{
		Daemon::log('beginRequest');
		if ($this->reqCounter===PHP_INT_MAX)
		{
			$this->reqCounter = 0;
		}
		$id                  = ++$this->reqCounter;
		$req->rid            = $id;
		$this->requests[$id] = $req;
		$this->sendPacket([
			'type' => 'startReq',
			'rid'  => $id,
			'req'  => $req->exportObject(),
		]);
	}

	public function endRequest($req)
	{
		unset($this->requests[$req->rid]);
		$this->sendPacket([
			'type' => 'endRequest',
			'rid'  => $req->rid,
		]);
	}

	/**
	 * @param $rid
	 * @param Block $block
	 */
	public function getBlock($rid, $block)
	{
		if (!isset($this->requests[$rid]))
		{
			Daemon::log(__METHOD__.': Unregistered request #'.$rid);
		}
		$req                = $this->requests[$rid];
		$bid                = ++$req->queriesCnt;
		$req->queries[$bid] = $block;
		//Daemon::log('Creating query '.@json_encode([$rid, $bid, $block['name'], $block['type']]));
		$this->sendPacket([
			'type'  => 'getBlock',
			'rid'   => $rid,
			'bid'   => $bid,
			'block' => $block->exportObject(),
		]);
	}

	public function sendPacket($p)
	{
		$data = igbinary_serialize($p);
		$this->write(pack('N', strlen($data)).$data);
	}

	/**
	 * Called when new data received.
	 * @param string $buf New data.
	 * @return void
	 */
	public function stdin($buf)
	{
		/** @q consider declare it as regular field */
		$this->buf .= $buf;

		start:

		if (strlen($this->buf) < 4)
		{
			return; // not ready yet
		}

		$u    = unpack('N', $this->buf);
		$size = $u[1];

		if (strlen($this->buf) < 4+$size)
		{
			return; // no ready yet;
		}

		$packet = binarySubstr($this->buf, 4, $size);

		$this->buf = binarySubstr($this->buf, 4+$size);

		$this->onPacket(igbinary_unserialize($packet));

		goto start;
	}
}