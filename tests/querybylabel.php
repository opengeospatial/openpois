<?php 
include_once('../class.poi.php');
$ps = POI::loadPOIsByLabel('Crosetta');
// var_dump($ps);

foreach($ps as $p) {
  echo $p->AsJSON(JSON_PRETTY_PRINT);
}
?>
