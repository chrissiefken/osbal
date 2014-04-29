<?php
include $_SERVER['DOCUMENT_ROOT'] . '/lib/header.php';
include $_SERVER['DOCUMENT_ROOT'] . '/lib/check.php';
$results = checkStatus();

echo '<ul class="list-group">';

foreach($results as $result) {
	if($result['error']==1){
		$error = '<span class="label label-danger pull-right">Error</span>';
	} else {
		$error = '';
	}
	echo '<li class="list-group-item">' . $result['message'] . $error . '</li>';
}

 echo '</ul>';



include $_SERVER['DOCUMENT_ROOT'] . '/lib/footer.php';
?>