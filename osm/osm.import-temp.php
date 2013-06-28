<?php

// build directory of temporary files
$file = '/srv/openpoidb/databases/osm/RI/dfs';
// $file = '/var/lib/postgresql/osm/planet-latest.osm';
$d = dirname($file);
$dr = $d . '/tmp';

// loop through each temporary file and import it into the openpoi db
if ($handle = opendir($dr)) {
  while ( FALSE !== ($entry = readdir($handle)) ) {
    if ( strpos($entry, '.') === 0 ) continue; // skip dot files
    
    $e = $dr . '/' . $entry;
    system("php osm.importone.php $e");
    echo "Done importing $e.\n";
  }
}

?>