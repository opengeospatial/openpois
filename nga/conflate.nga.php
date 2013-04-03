<?php

include_once('../utils.php');
include_once('conflate.php');

$irrelevantnames = array('V', 'U', 'R', 'T', 'H');
$maxdistance = 1000;
$fn = $projbase . '/databases/NGA/1000names.txt';
$fp = @fopen($fn, "r");

if ($fp) {
  $i = 0;
  while ( ($buffer=fgets($fp, 4096) ) != false ) {
    $i++;
    $vs = explode("\t", $buffer);
    
    // there will be 29 array members
    // lat is 4th, lon is 5th, name field we want (FULL_NAME_ND_RO) is 24th, 
    // UFI (Unique Feature Identifier) is 2nd, UNI (Unique Name Identifier) is 3rd, 
    // FC (Feature Classification) is 10th, and modified date (of format m/d/YY) is 29th
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
    $uni = $vs[2];
    echo "processing line $i with name $name [lat=$lat, lon=$lon]...\n";
    // echo("name: $name\tlon: $lon\tlat: $lat\n");
    $matches = getDistanceMatches($lon, $lat, $maxdistance);
    // echo "number of features within $maxdistance meters of $name: " . sizeof($matches) . "\n";
    $matches = getPOINames($name, $matches);
    // echo "num name matches: " . sizeof($matches) . "\n";

    if ( sizeof($matches) > 0 ) {
      $goodmatches = array();
      foreach($matches as $m) {
        $m->computeScore($maxdistance);
        if ( $m->score > 0.65 ) {
          $goodmatches[] = $m;
        }
      }
      if ( sizeof($goodmatches) > 0 ) {
        echo "$name at $lon,$lat matched:\n";
        var_dump($goodmatches);
      }
    }

  } // end while
  
} else {
  echo "Couldn't open file $fn for reading.\n";
}

fclose($fp);

?>