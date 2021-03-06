<?php
include 'syscfg.php';
$mongo = new MongoClient();
$db = $mongo->{$dbname};
chdir(__DIR__.'/..');
class WakePHP {
	public $name;
	public function __construct($path, $name) {
		$this->name = $name;
		$this->sock = stream_socket_client($path, $errno, $errstr);
		if (!$this->sock) {
			$e = new CouldntConnect;
			$e->path = $path;
			throw $e;
		}
	}
	public function sendPacket($p) {
		$data = serialize($p);
		fwrite($this->sock, pack('N', strlen($data)) . $data);
	}
	public function __call($m,$a) {
		$this->sendPacket(array(
					'op' => 'singleCall',
					'appfullname' => '\\WakePHP\\Core\\WakePHP'.($this->name?'-'.$this->name:''),
					'method' => $m,
					'args' => $a
		));
	}
}
class CouldntConnect extends Exception {}


try {
	$app = new WakePHP('unix://'.sprintf($mastersocket, crc32($a = $pidfile . "\x00" . $user. "\x00" . $group)),$name);
}
catch (CouldntConnect $e) {
		var_dump($a);
	exit('Connection failed to '.$e->path.'.');
}
