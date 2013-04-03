<?php

$file = '/Users/rajsingh/workspace/openpoi/databases/pleiades/pleiades-locations-20120404.csv.bz2';
$bz = bzopen($file, "r") or die ("Couldn't open $file for reading...\n");

while ( ($line=bzread($bz) ) != false ) {
  echo ($line."\n\n");
}

bzclose($bz);

?>