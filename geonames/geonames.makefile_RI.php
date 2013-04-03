<?php
require_once('constants.php');
require_once('geonames.constants.php');
require_once('utils.php');

/**
 * geonames.makefile_RI.php
 */
$copydir = '/Users/rajsingh/workspace/openpoi/databases/geonames/US/';

$us = fopen($copydir.'US.txt', "r");
$ri = fopen($copydir.'RI.txt', "w");

if ( FALSE === $us || FALSE === $ri ) {
  die("couldn't open file!!!\n");
}

$i = 0;
while ( ($line=fgets($us) ) != false ) {
  $i++;
  $vs = explode("\t", $line);
  
  $state = $vs[10];
  if ( $state != 'RI' ) continue;
  
  fwrite($ri, $line);
} // end while

fclose($us);
fclose($ri);

?>
