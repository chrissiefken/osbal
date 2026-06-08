<?php
require_once $_SERVER['DOCUMENT_ROOT'] . '/lib/global-settings.php';

function getUsersFile() {
    return config::getConfigDir() . config::userFile;
}

function getUsers() {
    $file = getUsersFile();
    if (!file_exists($file)) {
        return array();
    }
    $content = file_get_contents($file);
    $data = json_decode($content, true);
    return is_array($data) ? $data : array();
}

function createUser($username, $password) {
    $users = getUsers();
    $users[$username] = password_hash($password, PASSWORD_BCRYPT);
    file_put_contents(getUsersFile(), json_encode($users, JSON_PRETTY_PRINT), LOCK_EX);
}

function deleteUser($username) {
    $users = getUsers();
    if (isset($users[$username])) {
        unset($users[$username]);
        file_put_contents(getUsersFile(), json_encode($users, JSON_PRETTY_PRINT), LOCK_EX);
    }
}

function verifyUser($username, $password) {
    $users = getUsers();
    if (isset($users[$username])) {
        return password_verify($password, $users[$username]);
    }
    return false;
}

function checkAuth() {
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
    if (!isset($_SESSION['authenticated']) || $_SESSION['authenticated'] !== true) {
        header('Location: /index.php');
        exit;
    }
}
?>