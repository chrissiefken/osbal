<?php
header('Content-Type: application/json');
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

$username = isset($_POST['uname']) ? trim($_POST['uname']) : '';
$passwd = isset($_POST['passwd']) ? $_POST['passwd'] : '';

if (empty($username) || empty($passwd)) {
    echo json_encode(array('success' => false, 'message' => 'Username and password are required.'));
    exit;
}

try {
    createUser($username, $passwd);
    echo json_encode(array('success' => true, 'message' => 'User created successfully.'));
} catch (Exception $e) {
    echo json_encode(array('success' => false, 'message' => $e->getMessage()));
}
?>