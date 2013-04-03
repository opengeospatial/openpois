<?php 
include_once('class.poi.php');
$ps = POI::loadPOIsByLabel('Carnate');
echo $ps[0]->asXML();

?>
