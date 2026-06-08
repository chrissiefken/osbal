<?php
header('Content-Type: application/json');
require_once $_SERVER['DOCUMENT_ROOT'] . '/lib/network.php';
require_once $_SERVER['DOCUMENT_ROOT'] . '/lib/user.php';

// Enforce auth check if admin accounts are already initialized
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
$users = getUsers();
if (!empty($users)) {
    if (!isset($_SESSION['authenticated']) || $_SESSION['authenticated'] !== true) {
        header('HTTP/1.1 403 Forbidden');
        echo json_encode(array('success' => false, 'message' => 'Unauthorized access.'));
        exit;
    }
}

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