<?php
/**
 * Read in generic POI XML, try to match it to an existing POI in the database, then import.
 */
require_once('constants.php');
require_once('conflate.php');

//// read file and pull out each <poi> element
// $fn = "/databases/futouring/futouring_pois.xml";
$fn = "/databases/ourairports/1ec0ca5a-c727-470a-ac62-7f7d8391aba4.xml";
// $fn = "/databases/futouring/f.xml";
$fn = "/databases/b2b/manning.xml";
$file = $projbase . $fn;
$file_handle = fopen($file, "r");
$xmltext = '';
$innode = FALSE;
while (!feof($file_handle)) {  
  $line = trim( fgets($file_handle) );
  
  if ( $innode ) {
    $xmltext .= $line . "\n";

    if ( strpos($line, '</poi>') !== FALSE ) {  // THIS IS THE MONEY SECTION!!!!!      
      $poi = POI::loadXMLData( simplexml_load_string($xmltext) ); // take the text <poi> element and make it PHP
      $poi = conflatePOI($poi);   // NOW CONFLATE IT
      
      $newid = $poi->updateDB();  // AND PERSIST IT!!!!
      echo ("imported POI with ID: $newid and name: " . $poi->labels[0]->value . "\n");

      $innode = FALSE;
      $xmltext = '';
    }
  
  } else { // not $innode
    if ( strpos($line, '<poi') !== FALSE && strpos($line, '<pois') === FALSE ) {
      $innode = TRUE;
      $xmltext = $line;
    }
  }
}

function conflatePOI($poi, $maxdistance=2000) {
  // get distance matches
  $lat = (double)$poi->location->getY();
  $lon = (double)$poi->location->getX();
  if ( empty($lat) || empty($lon) ) return $poi;

  $matches = getDistanceMatches($lon, $lat, $maxdistance, 9);
  // echo "number of features within $maxdistance meters of $name: " . sizeof($matches) . "\n";

  // add name match scores to distance matches
  $name = $poi->labels[0]->value;
  $matches = getPOINames($name, $matches, FALSE);

  // select a top match
  $thematch = NULL;
  if ( $matches != NULL && sizeof($matches) > 0 ) {

    foreach($matches as $m) {
      $m->computeScore(); // echo "Match score is: $m->score\n";
    }

    foreach($matches as $m) {
      if ( ($thematch == NULL || $m->score > $thematch->score) && $m->poiuuid != NULL ) {
        $thematch = $m;
      }
    }
  }

  // make sure that top match is at least a certain score
  if ( $thematch != NULL && $thematch->score > 0.700 ) {
    $poimaster = POI::loadPOIUUID($thematch->poiuuid);
    $id = $poimaster->getMyId();
    echo "\nGOT A MATCH!!!!!!!!\n";
    echo "OSM POI: $name, lat: $lat, lon: $lon, ID: $id\n";
    echo "matched:\n";
    foreach ($thematch->labels as $label=>$score) {
      echo ($label . ">> distance: " . $thematch->dist);
      echo (" name score: " . $score . " total score: " . $thematch->score . "\n");
    }
    $poi = $poimaster->mergePOI($poi);
  } 
  return $poi;
}
?>
