<?php
function checkStatus(){

	$status = array();
	$errors = array();
	//check for packages

	$required = "heartbeat,haproxy,stunnel4,apache2,php5";
	$required = explode(',', $required);

	foreach($required as $package) {
		$status[] = checkRequirement($package);
	}

	//check config
	$status[] = checkConfig();

	return $status;
}

function checkRequirement($package) {
	$result = shell_exec('dpkg -s ' . $package);
	if(preg_match('/Status: install ok installed/', $result)){
		return array('message' => $package . ' installed', 'error' => false, 'type' => 'package');
	} else {
		return array('message' => $package . ' NOT installed', 'error' => true, 'type' => 'package');
	}
}

function checkConfig(){
	if(file_exists('/usr/local/osbal/config')){
		return array('message' => 'Config file exists.', 'error' => false, 'type' => 'config');
	} else {
		return array('message' => 'Config file missing.', 'error' => true, 'type' => 'config');
	}
}

?>