<?php

require_once("bootstrap.php");

$sleep_int = 200000;

if($_GET) {
	$remote = $_GET['remote'];
	$key = $_GET['key'];
	$chl = $_GET['chl'];

	if ($chl) {
		$nos = str_split($chl);
		foreach ($nos as $n) {
			print 'irsend("SEND_ONCE", '.$remote.', STB_'.$n.')';
			$success = irsend("SEND_ONCE", $remote, 'STB_'.$n);
			usleep($sleep_int);
		}
//		$success = irsend("SEND_ONCE", $remote, 'STB_OK');
	}

	if ($key) {
		$keys = explode(" ", $key);
		foreach ($keys as $k) {
			print 'irsend("SEND_ONCE", '.$remote.', '.$k.')';
			$success = irsend("SEND_ONCE", $remote, $k);
			usleep($sleep_int);
		}
	}
}

$db->close();

?>