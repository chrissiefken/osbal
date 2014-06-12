<?php
include $_SERVER['DOCUMENT_ROOT'] . '/lib/header.php';
?>
<h2>Online Services:</h2>
<div class="list-group">
  <a href="#" class="list-group-item">
    <h4 class="list-group-item-heading"><span class="glyphicon glyphicon-pencil"></span> 10.0.0.102</h4>
    <p class="list-group-item-text">site.example.com</p>
  </a>
  <a href="#" class="list-group-item">
    <h4 class="list-group-item-heading"><span class="glyphicon glyphicon-pencil"></span> 10.0.0.101</h4>
    <p class="list-group-item-text">api.example.com</p>
  </a>
  <a href="/lb-settings/createService.php" class="list-group-item">
    <h4 class="list-group-item-heading"><span class="glyphicon glyphicon-plus"></span> Create a new service</h4>
    </a>
</div>

<h2>Offline Services:</h2>
<div class="list-group warning">
  <a href="#" class="list-group-item list-group-item-danger">
    <h4 class="list-group-item-heading">List group item heading</h4>
    <p class="list-group-item-text">Donec id elit non mi porta gravida at eget metus. Maecenas sed diam eget risus varius blandit.</p>
  </a>
  <a href="#" class="list-group-item list-group-item-danger">
    <h4 class="list-group-item-heading">List group item heading</h4>
    <p class="list-group-item-text">Donec id elit non mi porta gravida at eget metus. Maecenas sed diam eget risus varius blandit.</p>
  </a>
</div>
<?
include $_SERVER['DOCUMENT_ROOT'] . '/lib/footer.php';
?>