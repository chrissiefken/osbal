<?php
include $_SERVER['DOCUMENT_ROOT'] . '/lib/header.php';

?>
	<div class="well">
		<form id="signup" class="form-horizontal" method="post" action="create.php">
			<legend>Create a new user.</legend>
			<div class="control-group">
				<label class="control-label">Username</label>
				<div class="controls">
					<div class="input-prepend">
						<span class="add-on"><i class="icon-user"></i></span>
						<input type="text" class="input-xlarge" id="uname" name="uname" placeholder="Username">
					</div>
				</div>
			</div>
			<div class="control-group">
				<label class="control-label">Password</label>
				<div class="controls">
					<div class="input-prepend">
						<span class="add-on"><i class="icon-lock"></i></span>
						<input type="Password" id="passwd" class="input-xlarge" name="passwd" placeholder="Password">
					</div>
				</div>
			</div>
			<div class="control-group">
				<label class="control-label">Confirm Password</label>
				<div class="controls">
					<div class="input-prepend">
						<span class="add-on"><i class="icon-lock"></i></span>
						<input type="Password" id="conpasswd" class="input-xlarge" name="conpasswd" placeholder="Re-enter Password">
					</div>
				</div>
			</div>

			<div class="control-group">
				<label class="control-label"></label>
				<div class="controls">
					<button type="submit" class="btn btn-success" >Create My Account</button>

				</div>

			</div>

		</form>

	</div>
<?php 
include $_SERVER['DOCUMENT_ROOT'] . '/lib/footer.php';
?>