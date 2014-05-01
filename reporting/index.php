<?php
include $_SERVER['DOCUMENT_ROOT'] . '/lib/header.php';
?>
<script>
$(function() {
    Morris.Area({
    element: 'graph-container',
    data: [
      { y: '2006', a: 100, b: 90 },
      { y: '2007', a: 75,  b: 65 },
      { y: '2008', a: 50,  b: 40 },
      { y: '2009', a: 75,  b: 65 },
      { y: '2010', a: 50,  b: 40 },
      { y: '2011', a: 75,  b: 65 },
      { y: '2012', a: 100, b: 90 }
    ],
    xkey: 'y',
    ykeys: ['a', 'b'],
    labels: ['Series A', 'Series B']
  });
});
</script>
<h2>Connections</h2>
<div id="graph-container"></div>
<?php
include $_SERVER['DOCUMENT_ROOT'] . '/lib/footer.php';
?>