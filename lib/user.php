<?php
require_once $_SERVER['DOCUMENT_ROOT'] . '/lib/global-settings.php';

function createUser($username, $password) {
	$osbalpass = $password;
	// Combine hashs.
	$md5pass- md5($osbalpass);
	$sha1pass = sha1($md5pass);
	$cryptpass = crypt($sha1pass, osbal14s2343a);
	
	echo config::configPath . '/' . config::userFile;
	file_put_contents(config::configPath . config::userFile, 
		$username . ':' . $sha1pass . "\n", 
		FILE_APPEND | LOCK_EX
		);
}

function deleteUser($username) {
	$arr = file(config::configPath . config::userFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
	$arr = array_filter($arr, function($item) use ($username) {
    	return $item != $username;
	});

	file_put_contents(config::configPath . config::userFile, join("\n", $arr));
}

function getUsers() {
	$arr = file(config::configPath . config::userFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
	return $arr;
}
?>