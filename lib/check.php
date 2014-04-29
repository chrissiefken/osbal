<?php

//check for heartbeat

$required = "heartbeat,haproxy,stunnel,apache2,php5";
$required = explode(',', $required);

foreach($required as $package) {
	checkRequirement($package);
}


function checkRequirement($package) {
	$result = shell_exec('dpkg -s ' . $package);
	if(preg_match('/Status: install ok installed/', $result)){
		echo $package . ' installed! <br />';
	} else {
		echo $package . ' NOT installed! <br />';
	}
}


?>