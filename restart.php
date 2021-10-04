<?php

require "ZteF609.php";

$ip = file_get_contents("https://myip.indihome.co.id/");
$ip = json_decode($ip);

echo "IP sekarang adalah $ip->ip_addr".PHP_EOL;

if(filter_var($ip->ip_addr, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE)) {
	echo "sdh dpt ip public".PHP_EOL;
}
else{
	echo "belum dpt ip public".PHP_EOL;

	$zteF609  = new ZteApi('192.168.2.254', 'admin', 'Telkomdso123', true);
	$zteF609->login();
	$zteF609->reboot();
}

@unset("cookie.txt");