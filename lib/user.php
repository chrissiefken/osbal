<?php




function createUser($username, $password){

	$osbalpass = $password;
	// Combine hashs.
	//$md5pass- md5($osbalpass);
	$sha1pass = sha1($md5pass);
	//$cryptpass = crypt($sha1pass, osbal14s2343a);
	
	//echo $sha1pass;
	echo $configPath . '/' . $userFile;
	file_put_contents($configPath . '/'. $userFile, $username . ':' . $sha1pass, FILE_APPEND | LOCK_EX);
	

}


?>