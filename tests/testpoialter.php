<?php
include_once('../utils.php');
include_once('../class.poi.php');

$ps = POI::loadPOIsByLabel('Grace Farrar Cole');

foreach($ps as $p) {
  $p->labels[0]->setHref('http://testpoialter.myogc.org');
  $p->updateDB();
  // echo $p->AsJSON(JSON_PRETTY_PRINT);
}


?>