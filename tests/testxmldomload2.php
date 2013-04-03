<?php
include ('class.poi.php');

// $l = new POITermType('label', 'lll is a dummy', 'LLL', 'http://www.example.com');
// echo $l->asXML();
$p = new POI();
$p->addLabel('LLL', 'lll is a big dummy', 'http://www.example.com');
$p->addLabel('VVVVV', 'vvvv is a bigger dummy', 'http://www.example.com');

$dom = new DOMDocument('1.0', 'UTF-8');
$dom->preserveWhiteSpace = false;
$dom->loadXML($p->asXML());
$dom->formatOutput = true;
echo $dom->saveXML();
echo "\n\nremoving first label...\n\n";
$p->removeLabel(0);

$p->addCenterPoint("42.432432 -72.6");

$dom->loadXML($p->asXML());
echo $dom->saveXML();

?>