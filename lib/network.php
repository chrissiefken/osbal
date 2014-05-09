<?php

require_once $_SERVER['DOCUMENT_ROOT'] . '/lib/global-settings.php';

function updateAdminIp($ip, $subnet, $gateway, $name){
	file_put_contents(config::configPath . config::adminIpSettings . '\n', $username . ':' . $cryptpass, FILE_APPEND | LOCK_EX);
}
