<?php

if (isset($_POST['submit-btn'])) {
	$username = $_POST['name'];
	$password = $_POST['password'];
	$user = $username . ',' . $password . '\n';
	$fp = fopen('accounts.txt', 'a+');

	if (fwrite($fp, $user)) {
		echo 'complete';
	}
	fclose($fp);
}

$osbalpass = $password;

// Combine hashs.
$md5pass- md5($osbalpass);
$sha1pass = sha1($md5pass);
$cryptpass = crypt($sha1pass, osbal14s2343a);

echo "$cryptpass";

