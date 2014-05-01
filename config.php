<?php

$dom = new DomDocument();
$dom->loadHTML($html);

$xpath = new DOMXPath($dom);

$tags = $xpath->query('//input[type=password]');
foreach ($tags as $tag) {
	var_dump(trim($tag->getAttribute('value')));
}

$osbalpass = ;

// Combine hashs.
$md5pass- md5($osbalpass);
$sha1pass = sha1($md5pass);
$cryptpass = crypt($sha1pass, osbal14s2343a);

echo "$cryptpass";

?>