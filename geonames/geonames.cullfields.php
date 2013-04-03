<?php
require_once('constants.php');
require_once('geonames.constants.php');
require_once('utils.php');

/**
 * import.geonames.php
 * Reads a geonames dump file and writes text files in PostgreSQL COPY format
 * THIS IS ONLY FOR TESTING! Writes a small subset of data -- NOT IN POI FORMAT!!
 */
$gndb = '/srv/openpoidb/databases/geonames/';
$fn = $gndb . 'it.txt';
$copydir = $gndb; // local

$n = '\N';

$fp = fopen($fn, "r");
$gout = fopen($copydir.'it.out', "w");

if ( FALSE === $fp) {
  die("couldn't open file, $fn!!!\n");
}

$i = 0;
while ( ($line=fgets($fp) ) != false ) {
  $i++;
  $vs = explode("\t", $line);
  
  // there will be 19 array members (but we only care about the first 9)
  // geonameid is the 1st, name is the 2nd, lat is 5th, lon is 6th, 
  // feature class is the 7th, feature code is 8th (both w scheme of http://www.geonames.org/export/codes.html)
  // and make feature class term fclass and feature code term fcode
  // country code is 9th and its term should be cc (what scheme??)
  $geonameid = $vs[0];
  $name = $vs[1];
  $lat = $vs[4];
  $lon = $vs[5];
  $fclass = $vs[6];
  $fcode = $vs[7];

  echo "processing line $i with id:$geonameid, name:$name [lat=$lat, lon=$lon]...\n";
  
  $rtxt = "$geonameid\t$name\t$fclass\t$fcode\tSRID=4326;POINT($lon $lat)\n";
  
  fwrite($gout, $rtxt);
} // end while -- on to the next POI

fclose($gout);
// COPY geonames from '/srv/openpoidb/databases/geonames/it.out';
?>
