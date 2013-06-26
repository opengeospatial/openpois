<?php
/**
 * load POI file from DB into a POI object
 */
include ('class.poi.php');

$reallydelete = false;
$pid = "afb03c00-9b1a-4a13-a722-618dba0aaa8e";

$argv = $_SERVER['argv'];
if ( empty($argv) || sizeof($argv) < 2 ) 
  die ("deletebyid.php POI_ID [REALLYDELETE]\n");
  
if ( isset($argv[1]) ) {
  $pid = $argv[1];
}

$trues = array("yes","y","1","TRUE","true","t","T");
if ( isset($argv[2]) ) {
  if ( in_array($argv[2], $trues) ) {
    $reallydelete = true;
  }
}

$p = POI::deleteDB($pid, false, $reallydelete);

if ( $p == FALSE ) {
  echo "Couldn't delete POI.\n";
  exit(-1);
}

?>