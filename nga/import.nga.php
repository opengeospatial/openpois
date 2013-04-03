<?php

/**
 * Reads an NGA Geonet dump file and writes text files in PostgreSQL COPY format
 */
include_once('../utils.php');
include_once('conflate.php');

$irrelevantnames = array('V', 'U', 'R', 'T', 'H');
$maxdistance = 1000;
$fn = $projbase . '/databases/nga/it.txt';
$fp = @fopen($fn, "r");
$on = $projbase . '/databases/nga/it.out';
$op = fopen($on, "w");

if ($fp) {
  $i = 0;
  while ( ($buffer=fgets($fp, 4096) ) != false ) {
    $i++;
    $vs = explode("\t", $buffer);
    
    // there will be 29 array members
    // lat is 4th, lon is 5th, name field we want (FULL_NAME_ND_RO) is 24th, 
    // UFI (Unique Feature Identifier) is 2nd, FC (Feature Classification) is 10th
    $fc = $vs[9];
    // echo ("name type: $fc\n");
    // echo ("relevant? "); var_dump(array_search($fc, $irrelevantnames)); echo "\n";
    if ( array_search($fc, $irrelevantnames) !== FALSE ) {
      echo "processing line $i -- irrelevant name, continuing...\n";
      continue;
    }

    $lon = $vs[4];
    $lat = $vs[3];
    $name = $vs[23];
    $ufi = $vs[1];
    // echo "processing line $i with name $name [lat=$lat, lon=$lon]...\n";

    $poitxt = "$ufi\t$fc\t$name\tSRID=4326;POINT($lon $lat)\n";
    fwrite($op, $poitxt);
  } // end while
  
} else {
  echo "Couldn't open file $fn for reading.\n";
}

fclose($fp);
fclose($op);

// COPY ngageonet from /srv/openpoidb/databases/nga/it.out
?>