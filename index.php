<?php 
include $_SERVER['DOCUMENT_ROOT'] . '/lib/check.php';
include $_SERVER['DOCUMENT_ROOT'] . 'config.php';
$config = checkConfig();

if($config['error'] == 1){
	
	$alert = '
		<div class="alert alert-dismissable alert-success">
		  <button type="button" class="close" data-dismiss="alert">×</button>
		  <strong>First Time Here?</strong> You have successfully installed OSBal, now you just need to configure it.<br /> 
		  <a href="install.php">Install Now.</a>
		</div>
	';
} else {
	$alert = '';
}


?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title>OSBal</title>
        <meta name="viewport" content="width=device-width, initial-scale=1">
    <link href="/css/bootstrap.min.css" rel="stylesheet" id="bootstrap-css">
    <style type="text/css">
    body{padding-top:20px;}    </style>
    <script src="//code.jquery.com/jquery-1.10.2.min.js"></script>
    <script src="/js/bootstrap.min.js"></script>
</head>
<body>
	<div class="container">
    <div class="row">
		<div class="col-md-4 col-md-offset-4">
			<?php echo $alert; ?>
    		<div class="panel panel-default">
			  	<div class="panel-heading">
			    	<h3 class="panel-title">Please sign in</h3>
			 	</div>
			  	<div class="panel-body">
			    	<form accept-charset="UTF-8" role="form">
                    <fieldset>
			    	  	<div class="form-group">
			    		    <input class="form-control" placeholder="E-mail" name="email" type="text">
			    		</div>
			    		<div class="form-group">
			    			<input class="form-control" placeholder="Password" name="password" type="password" value="">
			    		</div>
			    		<div class="checkbox">
			    	    	<label>
			    	    		<input name="remember" type="checkbox" value="Remember Me"> Remember Me
			    	    	</label>
			    	    </div>
			    		<input class="btn btn-lg btn-success btn-block" name="submit-btn" type="submit" value="Login">
			    	</fieldset>
			      	</form>
			    </div>
			</div>
			<p style="padding-left: 15px;"><a href="users/index.php">Create an account</a> | <a href="https://github.com/siefkencp/osbal">Learn More</a></p>
		</div>
	</div>
</div>
</body>
</html>
