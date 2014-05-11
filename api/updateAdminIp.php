<?php

require_once $_SERVER['DOCUMENT_ROOT'] . '/lib/network.php';

$ip = $_REQUEST['ip'];
$subnet = $_REQUEST['subnet'];
$gateway = $_REQUEST['gateway'];
$name = $_REQUEST['name'];

updateAdminIp($ip, $subnet, $gateway, $name);

?>