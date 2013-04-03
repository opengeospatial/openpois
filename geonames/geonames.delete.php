<?php
require_once('class.geonames.delete.php');

/**
* geonames.delete.php
* Deletes Geonames data from OpenPOI DB
* Input is the file to get deletes from. This script takes the Geonames ID, finds the POI that has 
* that link, deletes that link property, and then deletes all other properties in that POI whose 
* author is Geonames
*/

// this should be this program name and an absolute file name
$argv = $_SERVER['argv'];
if ( empty($argv) || sizeof($argv) < 2 ) 
  die ("geonames.delete.php: no arguments passed. Please send Geonames deletion files to me!\n");
$fn = $argv[1];

logToDB("geonames.delete.php processing $fn", 'UPDATEINFO');

$fp = fopen($fn, "r");
if ( FALSE === $fp) {
  logToDB("geonames.delete.php couldn't open $fn", 'UPDATEINFO');
  die("couldn't open file, $fn!!!\n");
}

$i = 0;
while ( ($line=fgets($fp) ) != false ) {
  $i++;
  $vs = explode("\t", $line);

  // there will be 4 array members (but we only care about the first, geonameid)
  $geonameid = $vs[0];
  echo "processing line $i with id:$geonameid\n";
  logToDB("geonames.delete.php deleting GN ID $geonameid", 'UPDATEINFO');

  GeonamesDeleter::delete($geonameid);
} // end while

?>
