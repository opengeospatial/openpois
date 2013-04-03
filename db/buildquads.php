<?php
include_once('constants.php');
include_once('utils.php');

# size is 0.025 x 0.025
$inc = 0.050;
$writetome = fopen("quads.in", "w");


$maxx = 180;
$maxy = 90;
for ($x=-$maxx; $x < $maxx; $x+=$inc) { 
  for ($y=-$maxy; $y < $maxy; $y+=$inc) {
    $xm = $x + $inc;
    $ym = $y + $inc;
    fwrite($writetome, "SRID=4326;POLYGON(($x $y,$x $ym,$xm $ym,$xm $y,$x $y))\n");
  }
}

// COPY quads (bbox) from '/Users/rajsingh/workspace/openpoi/application/db/quads.in';

?>