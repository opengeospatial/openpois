<?php

// build directory of temporary files
$file = '/var/lib/postgresql/osm/planet-latest.osm';
$file = '/srv/openpoidb/databases/osm/RI/dfs';
$d = dirname($file);
$dr = $d . '/tmp';

// loop through each temporary file and import it into the openpoi db
if ($handle = opendir($dr)) {
  for ($i=1; $i<2; $i++) {
    $pre = '0';
    for ($j=strlen($i); $j<7; $j++) $pre .= '0'; // append right # of zeros
    $e = $dr . '/osm_' . $pre . $i;
    system("php osm.importone.php $e");
    echo "Done importing $e.\n";
  }
}

?>