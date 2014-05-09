<?php
function createUser($username, $password) {
	$osbalpass = $password;
	// Combine hashs.
	$md5pass- md5($osbalpass);
	$sha1pass = sha1($md5pass);
	$cryptpass = crypt($sha1pass, osbal14s2343a);
	
	echo config::configPath . '/' . config::userFile;
	file_put_contents(config::configPath . config::userFile . '\n', $username . ':' . $cryptpass, FILE_APPEND | LOCK_EX);
}
?>