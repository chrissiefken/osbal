<?php
include $_SERVER['DOCUMENT_ROOT'] . '/lib/header.php';
include $_SERVER['DOCUMENT_ROOT'] . '/lib/check.php';
$results = checkStatus();

echo '<ul class="list-group">';

foreach($results as $result) {
	echo '<li class="list-group-item">' . $result . '</li>';
}

 echo '</ul>';


include $_SERVER['DOCUMENT_ROOT'] . '/lib/footer.php';
?>