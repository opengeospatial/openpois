<?php
require_once('constants.php');

$dbpedia_data_url = 'http://dbpedia.org/data/';
$st = strlen('http://dbpedia.org/resource/');

$fn = $projbase . '/databases/dbpedia/geo_coordinates_en.nt';
// logToDB("dbpedia.init.php processing $fn", 'UPDATEINFO');

$fp = fopen($fn, "r");
if ( FALSE === $fp) {
  // logToDB("dbpedia.init.php couldn't open $fn", 'UPDATEINFO');
  die("couldn't open file, $fn!!!\n");
}

//** process file with geo coordinates **//
while ( ($line=fgets($fp) ) != false ) {
  if ( strpos($line, 'georss/point') !== false ) {
    $a = explode("\"", $line);
    $b = explode(">", $line);
    $c = trim( $a[1] );
    $d = substr($b[0], 1);
    echo "Processing $d...\n";
    // sleep(300);
  }
  
}

?>