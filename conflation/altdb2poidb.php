<?php

include_once('utils.php');
include_once('class.matchcandidate.php');
include_once('conflate.php');

/**
 * Loop through all POIs in the alt database and conflate them with the main database
 */
try {
	$connalt = getDBAltConnection();
	$sql = "SELECT myid FROM poibasetype WHERE objname like 'POI' and deleted IS NULL";
  $c = $connalt->query($sql);
  foreach ( $c as $row) {
		$poi = NULL;
		
		/// load the POI into PHP
    $poiid = $row['myid'];
		$inpoi = POI::loadPOIUUID($poiid, $connalt);
		$name = $inpoi->getFirstLabelName();
		// echo "Doing POI ID: $poiid, name: $name and x: " . $inpoi->location->getX() . "\n";
		
		$matches = getDistanceMatches($inpoi->location->getX(), $inpoi->location->getY(), 125, 9);
		if ( $matches == NULL ) echo "No distance matches with ALT POI ID: $poiid, name: $name and x: " . $inpoi->location->getX() . "\n";
	  $matches = getPOINames($name, $matches, FALSE);
	  
	  //// select a top match and load that POI
	  $thematch = NULL;
	  if ( $matches != NULL && sizeof($matches) > 0 ) {
	    foreach($matches as $m) {
	      $m->computeScore();
	    }
	    foreach($matches as $m) {
	      if ( ($thematch == NULL || $m->score > $thematch->score) && $m->poiuuid != NULL ) {
	        $thematch = $m;
	      }
	    }
	  }
	  if ( $thematch != NULL && $thematch->score <= 0.500 ) {
echo "match id is " . $thematch->poiuuid . "\n";
	    $poi = POI::loadPOIUUID($thematch->poiuuid);
	    $id = $poi->getMyId();
	    echo "\nGOT A MATCH!!!!!!!!\n";
	    echo "ALT POI: $name ID: $poiid\n";
	    echo "matched: ID: $thematch->poiuuid\n";
	    foreach ($thematch->labels as $label=>$score) {
	      echo ($label . " dist score: " . $thematch->distscore);
	      echo (" name score: " . $score . " total score: " . $thematch->score . "\n");
	    }
	  }		

	  //// if there was no match, load the alt POI into the DB
		$inpoi->sanitize();
	  if ( $poi == null ) {
			$inpoi->updateDB();
			continue; //////// DONE!!
	  }

		//// if we are conflating, add the alt POI location as an alternate
		$inpoi->location->getFirstPoint()->setTerm('conflation');

	  // if there's no label, or no exact label match, then add a new label
	  $labelmatch = false;
	  $term = 'primary';
	  if ( !empty($poi->labels) && sizeof($poi->labels)>0 ) {
	    $term = 'conflation';
	    foreach ($poi->labels as $pl) {
	      $plname = $pl->getValue();
	      if ( $plname == $name ) {
	        $labelmatch = true;
	      }
	    }
	  }
	  if ( !$labelmatch ) $inpoi->labels[0]->setTerm($term);
	
		$poi->mergePOI($inpoi, true);
		
   } // end foreach POI id in alt database
	
} catch (Exception $e) {
  // logToDB("$d: Load of $fn failed: " . $e->getMessage());
  echo "Something failed: \n" . $e->getMessage() . "\n";
}

?>