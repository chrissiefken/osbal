<?php
include $_SERVER['DOCUMENT_ROOT'] . '/lib/global-settings.php';
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
    <link rel="stylesheet" href="http://cdn.oesmith.co.uk/morris-0.4.3.min.css">
	<script src="//cdnjs.cloudflare.com/ajax/libs/raphael/2.1.0/raphael-min.js"></script>
	<script src="http://cdn.oesmith.co.uk/morris-0.4.3.min.js"></script>
</head>
<body>
	<div class="container">
		<div class="navbar navbar-default">
		  <div class="navbar-header">
		    <button type="button" class="navbar-toggle" data-toggle="collapse" data-target=".navbar-responsive-collapse">
		      <span class="icon-bar"></span>
		      <span class="icon-bar"></span>
		      <span class="icon-bar"></span>
		    </button>
		    <a class="navbar-brand" href="/install.php">OSBal</a>
		  </div>
		  <div class="navbar-collapse collapse navbar-responsive-collapse">
		    <ul class="nav navbar-nav">
		      <li><a href="/reporting/index.php">Reporting</a></li>
		      <li><a href="/lb-settings/index.php">Load Balancer</a></li>
		      <li><a href="#">Certificates</a></li>
		      <li><a href="#">System Status</a></li>
		      <li><a href="users/index.php">User</a></li>
		    </ul>
		    <ul class="nav navbar-nav navbar-right">
		      <li><a href="https://github.com/siefkencp/osbal">About</a></li>
		    </ul>
		  </div>
		</div>