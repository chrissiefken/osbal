<?php
header('Content-Type: application/json');
require_once $_SERVER['DOCUMENT_ROOT'] . '/lib/global-settings.php';
require_once $_SERVER['DOCUMENT_ROOT'] . '/lib/user.php';

// Enforce auth check
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
if (!isset($_SESSION['authenticated']) || $_SESSION['authenticated'] !== true) {
    echo json_encode(array('success' => false, 'message' => 'Unauthorized access.'));
    exit;
}

require_once $_SERVER['DOCUMENT_ROOT'] . '/lib/services.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $rawIps = isset($_POST['blacklist']) ? $_POST['blacklist'] : '';
    
    // Split by comma, newline or space
    $ips = preg_split('/[\r\n, ]+/', $rawIps);
    $ips = array_filter(array_map('trim', $ips));
    
    // Validate IP structures
    $validIps = array();
    foreach ($ips as $ip) {
        if (filter_var($ip, FILTER_VALIDATE_IP)) {
            $validIps[] = $ip;
        } else {
            echo json_encode(array('success' => false, 'message' => 'Invalid IP address format: ' . htmlspecialchars($ip)));
            exit;
        }
    }
    
    try {
        saveBlacklist($validIps);
        echo json_encode(array('success' => true, 'message' => 'WAF Blacklist updated successfully.'));
    } catch (Exception $e) {
        echo json_encode(array('success' => false, 'message' => $e->getMessage()));
    }
} else {
    echo json_encode(array('success' => false, 'message' => 'Invalid request method.'));
}
?>
