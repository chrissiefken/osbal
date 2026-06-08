<?php
header('Content-Type: application/json');
require_once $_SERVER['DOCUMENT_ROOT'] . '/lib/user.php';

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