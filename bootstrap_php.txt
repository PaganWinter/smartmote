<?php

require_once("db.config.php");

$db = new mysqli($databases['remote']['host'], $databases['remote']['username'], $databases['remote']['password'], $databases['remote']['database']);

function irsend($command, $remote, $key) {
	if (!$handle = popen("/usr/bin/irsend $command \"$remote\" \"$key\" 2>&1","r")) {
		echo ("Could not fork irsend");
	}
	$output="";

	while (!feof($handle)) {
		$output = $output . fgets($handle, 1024);
	}
	pclose($handle);
	return split("\n", str_replace("/usr/bin/irsend: ", "", $output));
}

function keys($remote) {
	print($remote. "<br>\n");
	$keys = irsend("list",$remote,"");
	for ($i = 0; $i < count($keys);$i++) {
		list($label,$code,$keyname) = split(" ",$keys[$i]);
		print ("<a href=?remote=" . $remote .  "&key=" . $keyname . ">" . $keyname . "<br>\n");
	}
	print("<a href=remote.php>return</a>");
}


function x($var) {
  print "<pre>".print_r($var, 1)."</pre>";
}

function hp($var) {
  print "$var<br />";
}

?>