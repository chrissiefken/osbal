<?php
include $_SERVER['DOCUMENT_ROOT'] . '/lib/header.php';
?>
<h1>Add a New Service:</h1>
<form class="form-horizontal">
<div class="well">
    	<fieldset>
    		<div class="form-group">
		      <label for="gateway" class="col-lg-2 control-label">Service Name</label>
		      <div class="col-lg-10">
		        <input type="text" class="form-control" id="name" placeholder="Service Name">
		      </div>
		    </div>
    		<div class="form-group">
		      <label for="ip" class="col-lg-2 control-label">Service IP</label>
		      <div class="col-lg-10">
		        <input type="text" class="form-control" id="ip" placeholder="Service IP">
		      </div>
		    </div>
		    <div class="form-group">
	            <label class="col-lg-2 control-label">Transport Mode:</label>
	            <div class="col-lg-10">
	              <div class="radio">
	                <label>
	                  <input type="radio" name="mode" id="http" value="http" checked="">
	                  HTTP
	                </label>
	              </div>
	              <div class="radio">
	                <label>
	                  <input type="radio" name="mode" id="tcp" value="tcp">
	                  TCP
	                </label>
	              </div>
	            </div>
	          </div>
		    <div class="form-group">
	            <label class="col-lg-2 control-label">Balancing Strategy:</label>
	            <div class="col-lg-10">
	              <div class="radio">
	                <label>
	                  <input type="radio" name="optionsRadios" id="roundrobin" value="roundrobin" checked="">
	                  Round Robin
	                </label>
	              </div>
	              <div class="radio">
	                <label>
	                  <input type="radio" name="optionsRadios" id="cookie" value="cookie">
	                  Cookie Based
	                </label>
	              </div>
	              <div class="radio">
	                <label>
	                  <input type="radio" name="optionsRadios" id="ip" value="ip">
	                  IP Based
	                </label>
	              </div>
	            </div>
	          </div>
		    <div class="pull-right">
				<button id="create-btn" class="btn btn-success btn-lg">Create Service</button>
				<button id="create-btn" class="btn btn-default btn-lg">Cancel</button>
			</div>
		</fieldset>
    </div>
</form>

<?
include $_SERVER['DOCUMENT_ROOT'] . '/lib/footer.php';
?>