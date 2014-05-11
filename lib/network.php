<?php

require_once $_SERVER['DOCUMENT_ROOT'] . '/lib/global-settings.php';

function updateAdminIp($ip, $subnet, $gateway, $name){

	file_put_contents(
		config::configPath . config::adminIpSettings, 
		'ip' . ':' . $ip . "\n" .
		'subnet' . ':' . $subnet . "\n" .
		'gateway' . ':' . $gateway . "\n" .
		'hostname' . ':' . $name . "\n",
		LOCK_EX
		);
}
