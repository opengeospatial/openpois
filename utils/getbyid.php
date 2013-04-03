<?php
/**
 * load POI file from DB into a POI object
 */
include ('class.poi.php');

$pid = '067807ed-7dc8-41ab-8e7c-dcea5efda6fb';

$argv = $_SERVER['argv'];
if ( empty($argv) || sizeof($argv) < 2 ) 
  die ("getbyid.php POI_ID\n");
  
if ( isset($argv[1]) ) $pid = $argv[1];

$p = POI::loadPOIUUID($pid, false, $reallydelete);

if ( $p == FALSE ) {
  echo "Couldn't load POI.\n";
  exit(-1);
}

echo $p->asXML();
?>