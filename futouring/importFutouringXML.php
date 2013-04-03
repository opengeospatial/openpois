<?php
/**
 * Read in Futouring POI XML, try to match it to an existing POI in the database, then import.
 */
require_once('constants.php');
require_once('conflate.php');

//// read file and pull out each <poi> element
$fn = "/databases/futouring/futouring_pois.xml";
// $fn = "/databases/futouring/ffff.xml";
$file = $projbase . $fn;
$file_handle = fopen($file, "r");
$xmltext = '';
$innode = FALSE;
while (!feof($file_handle)) {  
  $line = trim( fgets($file_handle) );
  
  if ( $innode ) {
    $xmltext .= $line . "\n";

    if ( strpos($line, '</poi>') !== FALSE ) {  // THIS IS THE MONEY SECTION!!!!!      
      $poi = loadXMLData( simplexml_load_string($xmltext) ); // take the text <poi> element and make it PHP
      // echo "POI:\n" . var_dump($poi) . "\n";
      $poi = conflatePOI($poi); // NOW CONFLATE IT
      
      $newid = $poi->updateDB(); // AND PERSIST IT!!!!
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

function conflatePOI($poi, $maxdistance=500) {
  // get distance matches
  $lat = (double)$poi->location->getY();
  $lon = (double)$poi->location->getX();
  if ( empty($lat) || empty($lon) ) return $poi;

  $matches = getDistanceMatches($lon, $lat, $maxdistance, 9);
  $name = $poi->labels[0]->value;
  echo "number of features within $maxdistance meters of $name: " . sizeof($matches) . "\n";

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
  if ( $thematch != NULL && $thematch->score < 0.200 ) {
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

/**
 * Copy data from a SimpleXMLElement into a POI PHP object
 * @param xml SimpleXMLElement
 */
function loadXMLData($xml, $typename='POI', $poi=NULL) {
  $poi = new POI( gen_uuid(), NULL );
  $poi = POIBaseType::loadXMLData($xml, 'POI', $poi);
  $poi->setId( $poi->getMyId() );

  $lk = new POITermType('LINK', 'related');
  $lk->setHref( $xml['id'] );
  $lk->setId( $xml['id'] );
  $poi->addLink($lk);

  foreach ( $xml->label as $label) {
    $l = POITermType::loadXMLData($label);
    $l->value = $label['term'];
    $l->term = 'primary';
    $l->setAuthor( getFutouringAuthor() );
    $l->setLicense( getFutouringLicense() );
    $poi->addLabel($l);
  }

  foreach ( $xml->description as $description) {
    $d = POIBaseType::loadXMLData($description);
    $d->setAuthor( getFutouringAuthor() );
    $d->setLicense( getFutouringLicense() );
    $poi->addDescription($d);
  }

  foreach ( $xml->category as $category) {
    $c = POITermType::loadXMLData($category);
    $c->setAuthor( getFutouringAuthor() );
    $c->setLicense( getFutouringLicense() );
    $poi->addCategory($c);
  }

  foreach ( $xml->time as $time) {
    $t = POITermType::loadXMLData($time);
    $t->setAuthor( getFutouringAuthor() );
    $t->setLicense( getFutouringLicense() );
    $poi->times[] = $t;
  }

  foreach ( $xml->link as $link) {
    $l = POITermType::loadXMLData($link);
    $l->setAuthor( getFutouringAuthor() );
    $l->setLicense( getFutouringLicense() );
    $poi->times[] = $l;
  }

  foreach ( $xml->location as $location) {
    $l = Location::loadXMLData($location, NULL, $poi->location);
    if ( !empty($l->points[0]) ) {
      $l->points[0]->setAuthor( getFutouringAuthor() );
      $l->points[0]->setLicense( getFutouringLicense() );
    }
    $a = $l->getAddress();
    if ( !empty($a) ) {
      $l->getAddress()->setAuthor( getFutouringAuthor() );
      $l->getAddress()->setLicense( getFutouringLicense() );
    }
    
    $rs = $l->getRelationships();
    if ( !empty($rs) ) {
      foreach ( $rs as $r ) {
        // if the relationship points to a Futouring ID, change that to an OpenPOI ID
        // find that ID by looking for a link with an ID equal to the relationship's targetPOI value
        $t = $r->getTargetPOI();
        if ( strpos($t, 'http://www.futouring.com/download/dataset/futouring_pois.rdf') == 0 ) {
          // look for link with ID equal to $t
          $futlink = findPOITermTypeByProperty('LINK', 'id', $t);
          if ( !empty($futlink) ) {
            $pid = $futlink->getParentId();
            if ( !empty($pid) ) {
              $relatedpoi = POI::loadPOIUUID( $pid );
              $r->setTargetPOI( $relatedpoi->getId() );
            }
          }
        }
        $r->setAuthor( getFutouringAuthor() );
        $r->setLicense( getFutouringLicense() );
      }
    }
    $poi->location = $l;
  }
  
  return $poi;
}

?>
