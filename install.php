<?php
include $_SERVER['DOCUMENT_ROOT'] . '/lib/header.php';
include $_SERVER['DOCUMENT_ROOT'] . '/lib/check.php';
$results = checkStatus();

$error_count = 0;

$list = '<ul class="list-group">';

foreach($results as $result) {
	if($result['error']==1 && $result['type'] == 'package'){
		$error = '<span class="label label-danger pull-right">Error</span>';
		$error_count += 1;
	} else if ($result['error']==0 && $result['type'] == 'config'){
		$error = '<span class="label label-danger pull-right">Already Configured!</span>';
	} else {
		$error = '<span class="label label-success pull-right">Ready</span>';
	}
	$list .= '<li class="list-group-item">' . $result['message'] . $error . '</li>';
}

 $list .= '</ul>';

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
  			<strong>Passed!</strong> We are ready to go, el captian.
		</div>
 	';
}
?>

<div id="step-1">
<?php
echo $list;
echo $alert;
?>
<div class="pull-right">
	<button id="next-btn" class="btn btn-default btn-lg" onclick="$('#step-1').hide(); $('#step-2').show();">Next</button>
</div>
</div>

<div id="step-2" style="display:none;">
	<form class="form-horizontal">
  <fieldset>
    <legend>Initialize Settings</legend>
    <div class="form-group">
      <label for="userName" class="col-lg-2 control-label">User Name</label>
      <div class="col-lg-10">
        <input type="text" class="form-control" id="userName" value="admin">
      </div>
    </div>
    <div class="form-group">
      <label for="inputPassword" class="col-lg-2 control-label">Password</label>
      <div class="col-lg-10">
        <input type="password" class="form-control" id="inputPassword" placeholder="Password">
      </div>
    </div>
    <div class="well">
    	<fieldset>
    		<div class="form-group">
		      <label for="ip" class="col-lg-2 control-label">Management IP Address (This Device Only)</label>
		      <div class="col-lg-10">
		        <input type="text" class="form-control" id="ip" value="<?php echo $_SERVER['SERVER_ADDR']; ?>">
		      </div>
		    </div>
		    <div class="form-group">
		      <label for="subnet" class="col-lg-2 control-label">Subnet</label>
		      <div class="col-lg-10">
		        <input type="text" class="form-control" id="subnet" placeholder="255.255.255.0">
		      </div>
		    </div>
		    <div class="form-group">
		      <label for="gateway" class="col-lg-2 control-label">Gateway</label>
		      <div class="col-lg-10">
		        <input type="text" class="form-control" id="gateway" placeholder="10.0.0.1">
		      </div>
		    </div>
		    <div class="form-group">
		      <label for="name" class="col-lg-2 control-label">Friendly Name</label>
		      <div class="col-lg-10">
		        <input type="text" class="form-control" id="name" placeholder="<?php echo $_SERVER['SERVER_NAME'] ?>">
		      </div>
		    </div>
    	</fieldset>
    </div>
  </fieldset>
</form>
<div class="pull-right">
	<button id="next-btn" class="btn btn-default btn-lg" onclick="$('#step-2').hide(); $('#step-3').show();">Next</button>
</div>
</div><!-- div step-2 -->

<div id="step-3" style="display:none;">
	<h2>Bombs away!</h2>
	<p>You are now ready to start using your load balancer! Just click below to restart with your new settings.</p>
	<div class="pull-right">
		<button id="next-btn" class="btn btn-default btn-lg" onclick="alert('restarting');">Restart</button>
	</div>
</div>


<?php 
include $_SERVER['DOCUMENT_ROOT'] . '/lib/footer.php';
?>