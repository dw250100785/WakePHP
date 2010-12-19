<?php
include 'syscfg.php';
$mongo = new Mongo();
$db = $mongo->{$dbname};
chdir(__DIR__.'/..');
class WakePHP {
	public $name;
	public function __construct($path,$name) {
		$this->name = $name;
		$this->sock = stream_socket_client($path, $errno, $errstr);
		if (!$this->sock) {
			$e = new CouldntConnect;
			$e->path = $path;
			throw $e;
		}
	}
	public function sendPacket($p) {
		fwrite($this->sock,json_encode($p)."\n");
	}
	//public function saveBlock($block) {file_put_contents('./PackagedThemes/simple/blocks/'.$block['name'],json_encode($block));}
	public function __call($m,$a) {
		$this->sendPacket(array(
					'op' => 'singleCall',
					'appfullname' => get_class($this).'-'.$this->name,
					'method' => $m,
					'args' => $a
		));
	}
}
class CouldntConnect extends Exception {}


try {
	$app = new WakePHP('unix://'.sprintf($mastersocket, crc32($pidfile)),$name);
}
catch (CouldntConnect $e) {
	exit('Connection failed to '.$e->path.'.');
}
