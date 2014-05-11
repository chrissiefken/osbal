<?php

require_once $_SERVER['DOCUMENT_ROOT'] . '/lib/user.php';


$username = $_REQUEST['uname'];
$passwd = $_REQUEST['passwd'];


createUser($username,$passwd);

?>