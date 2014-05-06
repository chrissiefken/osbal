<?php
include $_SERVER['DOCUMENT_ROOT'] . '/lib/header.php';
include $_SERVER['DOCUMENT_ROOT'] . '/lib/user.php';


$username = $_REQUEST['uname'];
$passwd = $_REQUEST['passwd'];


createUser($username,$passwd);

include $_SERVER['DOCUMENT_ROOT'] . '/lib/footer.php';
?>