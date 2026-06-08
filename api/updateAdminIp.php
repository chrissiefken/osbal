<?php
header('Content-Type: application/json');
require_once $_SERVER['DOCUMENT_ROOT'] . '/lib/network.php';

$ip = isset($_POST['ip']) ? trim($_POST['ip']) : '';
$subnet = isset($_POST['subnet']) ? trim($_POST['subnet']) : '';
$gateway = isset($_POST['gateway']) ? trim($_POST['gateway']) : '';
$name = isset($_POST['name']) ? trim($_POST['name']) : '';

if (empty($ip) || empty($subnet) || empty($gateway) || empty($name)) {
    echo json_encode(array('success' => false, 'message' => 'All network settings are required.'));
    exit;
}

try {
    updateAdminIp($ip, $subnet, $gateway, $name);
    echo json_encode(array('success' => true, 'message' => 'Network settings saved successfully.'));
} catch (Exception $e) {
    echo json_encode(array('success' => false, 'message' => $e->getMessage()));
}
?>