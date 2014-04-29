<?php
include $_SERVER['DOCUMENT_ROOT'] . '/lib/header.php';
include $_SERVER['DOCUMENT_ROOT'] . '/lib/check.php';
$results = checkStatus();

$error_count = 0;

echo '<ul class="list-group">';

foreach($results as $result) {
	if($result['error']==1){
		$error = '<span class="label label-danger pull-right">Error</span>';
		$error_count += 1;
	} else {
		$error = '<span class="label label-success pull-right">Ready</span>';
	}
	echo '<li class="list-group-item">' . $result['message'] . $error . '</li>';
}

 echo '</ul>';

if($error_count != 0){
 	$alert = '
		<div class="alert alert-dismissable alert-danger">
  			<button type="button" class="close" data-dismiss="alert">×</button>
  			<strong>Oh snap!</strong> Please finish installing all packages and refresh this page.
		</div>
 	';
} else {
	$alert = '
		<div class="alert alert-dismissable alert-success">
  			<button type="button" class="close" data-dismiss="alert">×</button>
  			<strong>Ready to Rock!</strong> We are ready to go, el captian.
		</div>
 	';
}
echo $alert;

?>



<?php 
include $_SERVER['DOCUMENT_ROOT'] . '/lib/footer.php';
?>