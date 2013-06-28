<?php
/**
 * osm.import.php
 * This script starts the OpenStreetMap import
 * It splits an OSM XML file that's compressed with BZIP2 compression 
 * into approx. 10,000 line files, then runs osm.importone.php on each 
 * file. A tmp directory is created in the same location as the 
 * import file, and deleted upon completion
 */
include_once('constants.php');
include_once('utils.php');
include_once('osm.utils.php');

$is_compressed = FALSE;

// $fn = "/databases/osm/Massachusetts/mass40klines.osm.bz2"; 
// $fn = "/databases/osm/Massachusetts/massachusetts.osm.bz2";
$fn = "/databases/osm/RI/rhode-island-latest.osm";
// $fn = "/databases/osm/China/china.osm.bz2";
$file = $projbase . $fn;
// $file = '/var/lib/postgresql/osm/planet-latest.osm';

// logToDB("osm.import.php load of $file started", 'IMPORTINFO');
echo("osm.import.php load of $file started: IMPORTINFO\n");

// build directory of temporary files
$d = dirname($file);
$dr = $d . '/tmp';

// delete temp directory if it's there
if ( file_exists($dr) ) {
  shell_exec("rm -r $dr");
}

if ( !mkdir($dr) ) 
  exit($_SERVER['SCRIPT_NAME'] . " Couldn't make directory $dr\n");

$filesuffix = 1;
$linecounter = 0;
$linemax = 1000000;


if ( $is_compressed )
  $file_handle = bzopen($file, "r") or die ("Couldn't open $file for reading...\n");
else 
  $file_handle = fopen($file, "r");

$n = leading_zeros("$filesuffix", 8);
$writetome = fopen($dr . '/osm_' . $n, "w");
fwrite($writetome, "<?xml version=\"1.0\" encoding=\"UTF-8\"?>\n");
fwrite($writetome, "<osm version=\"0.6\" generator=\"osm.import.php\">\n");

echo("running osm.import.php with file $file: IMPORTINFO\n"); 
//logToDB("running osm.import.php with file $file", 'IMPORTINFO'); 

$innode = FALSE;
while (!feof($file_handle)) {  
  $line = fgets($file_handle);

  // $line = bzread($file_handle, 8192);
  // if ( $line === FALSE ) die("Read problem!\n");
  // if ( bzerror($file_handle) !== 0 ) die("Compression problem!\n");
  
  if ( $innode ) {
    fwrite($writetome, $line); // first, write this line
    $linecounter++;
    if ( strpos($line, '</node>') !== FALSE ) { // then, if it closes the node, say we're not in a node anymore
      $innode = FALSE;
      if ( $linecounter >= $linemax ) { // and then close the file if it's at the maximum size
        fwrite($writetome, "</osm>\n"); // close the XML
        fclose($writetome); //then close that file
        flush();
        $filesuffix++;
        $n = leading_zeros("$filesuffix", 8);
        $writetome = fopen($dr.'/osm_'.$n, "w"); // make a new file
        fwrite($writetome, "<?xml version=\"1.0\" encoding=\"UTF-8\"?>\n");
        fwrite($writetome, "<osm version=\"0.6\" generator=\"osm.import.php\">\n");
        $linecounter = 0; //and start line counting over
      }
    }
  
  } else { // we're not in a node yet
    // skip nodes that have no subelements
		$regline = trim($line);
    if ( strpos($regline, '<node id=') === 0 && substr_compare($regline, '/>', -2, 2) === 0) {
      continue;

    } else { // we aren't innode and it's not a single-line node
      if ( strpos($regline, '<node id=') !== FALSE ) { // if it's the start of a node, write it and go innode
        $innode = TRUE;
        fwrite($writetome, $line);
        $linecounter++;
      }
    }  
  }
}

//// clean up
fwrite($writetome, "</osm>");
if ($is_compressed)
  bzclose($file_handle);
else 
  fclose($file_handle);

//// loop through each temporary file and import it into the openpoi db
if ($handle = opendir($dr)) {
   while ( FALSE !== ($entry = readdir($handle)) ) {
     if ( strpos($entry, '.') === 0 ) {
       continue;
     }
     
     $e = $dr . '/' . $entry;
     system("php osm.importone.php $e");
     echo "Done importing $e.\n";
   }
}

// delete temp directory if it's there
// if ( file_exists($dr) ) {
//   shell_exec("rm -r $dr");
// }

//logToDB("osm.import.php load of $file success", 'IMPORTINFO');
echo("osm.import.php load of $file success: IMPORTINFO\n");
echo "Load of $file SUCCESS!\n";

?>