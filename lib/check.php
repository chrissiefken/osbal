<?php
function checkStatus(){

	$status = array();
	$errors = array();
	//check for packages

	$required = "heartbeat,haproxy,stunnel,apache2,php5";
	$required = explode(',', $required);

	foreach($required as $package) {
		$status[] = checkRequirement($package);
	}

	//check for pre-existing config
	if(file_exists('/usr/local/osbal/config')){
		$status[] = 'Config file exists.';
	} else {
		$status[] = 'Config file missing';
	}

	return $status;
}

function checkRequirement($package) {
	$result = shell_exec('dpkg -s ' . $package);
	if(preg_match('/Status: install ok installed/', $result)){
		return $package . ' installed';
	} else {
		return $package . ' NOT installed';
	}
}


?>