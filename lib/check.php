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

	//check config
	$status[] = checkConfig();

	return $status;
}

function checkRequirement($package) {
	$result = shell_exec('dpkg -s ' . $package);
	if(preg_match('/Status: install ok installed/', $result)){
		return array('message' => $package . ' installed', 'error' => false);
	} else {
		return array('message' => $package . ' NOT installed', 'error' => true);
	}
}

function checkConfig(){
	if(file_exists('/usr/local/osbal/config')){
		return array('message' => 'Config file exists.', 'error' => false);
	} else {
		return array('message' => 'Config file missing', 'error' => true);
	}
}

?>