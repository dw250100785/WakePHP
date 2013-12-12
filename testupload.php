<?php
$body = json_encode([
	'setBlocks' => [
		[
			'h' => base64_encode('1234'),
			'fn' => 0,
			's' => 1024,
			'fileRevId' => '5208bfeb191c2b7e2c00001a',
			'rnd' => 'JR.RoV0v',
		],
	]
]);
$sock = fsockopen('bitfile.me', 80);
fwrite($sock,
	$req =  "POST /component/BStorage/FileRevSetBlocks HTTP/1.1\r\n"
	. "Authorization: Basic MToxMTE=\r\n"
	. "Host: bitfile.me\r\n"
	. "Accept: */*\r\n"
	. "Content-Length: " . strlen($body) . "\r\n"
	. "Content-Type: application/json\r\n"
	. "Connection: close\r\n"
	. "\r\n" . $body
);
echo $req . PHP_EOL . '------------------------------------------------' . PHP_EOL;
fpassthru($sock);

function crlf($s) {
	$s = str_replace("\r", "", $s);
	$s = str_replace("\n", "\r\n", $s);
	return $s;
}
$body = crlf('------------------------------e5be2a69d4aa
Content-Disposition: form-data; name="file"; filename="test.txt"
Content-Type: text/plain

1
------------------------------e5be2a69d4aa--
');
$req = crlf('POST /component/BStorage/UploadFile/json HTTP/1.1
User-Agent: libcurl-agent/1.0
Host: bitfile.me
Accept: */*
Connection: close
Referer: http://bitfile.me/
Expect: 100-continue
Content-Length: '.strlen($body).'
Content-type: multipart/form-data; boundary=----------------------------e5be2a69d4aa

') . $body;


echo json_encode($req);

$fp = fsockopen('bitfile.me', 80);
fwrite($fp, $req);
fpassthru($fp);
