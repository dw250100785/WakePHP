<?php
$id = new MongoId;
$id = new MongoId;

$str = (string) $id;
$sbin = '';
$len = strlen( $str );
for ( $i = 0; $i < $len; $i += 2 ) {
	$sbin .= pack( "H*", substr( $str, $i, 2 ) );
}

echo base64_encode($sbin);

echo PHP_EOL;
echo PHP_EOL;