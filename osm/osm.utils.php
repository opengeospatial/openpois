<?php
require_once('constants.php');
require_once('conflate.php');

$osmbaseurl = 'http://www.openstreetmap.org';
$osmdataurl = 'http://www.openstreetmap.org/api/0.6/node';
$osmweburl = 'http://www.openstreetmap.org/browse/node';
$osmbasedir = '/srv/openpoidb/databases/osm/tmp/';
$category_scheme = 'http://wiki.openstreetmap.org/wiki/Map_Features';
$goodcategories = array('leisure','amenity','office','shop','craft','emergency','tourism','historic','aeroway','place','sport');
$badcategories = array('created_by','address','attribution', 'gnis:created','gnis:reviewed','gnis:created','gnis:import_uuid');
$descriptioncategories = array('description');

function deleteOSMData($poi) {
  global $osmbaseurl;

  $propcats = array();
  $propcats[] = $poi->labels;
  $propcats[] = $poi->categories;
  $propcats[] = $poi->links;  
  foreach ($propcats as $props) {
    foreach ($props as $prop) {
      if ( $prop->author->id == $osmbaseurl ) 
        $prop->deleteDB($prop->myid, NULL, 'poitermtype');
    }
  }
  
  $propcats[] = $poi->descriptions;
  foreach ($poi->descriptions as $prop) {
    if ( $prop->author->id == $osmbaseurl ) 
      $prop->deleteDB($prop->myid, NULL, 'poibasetype');
  }
  
}

/**
 * Takes an OpenStreetMap <node> and converts to OGC POI properties.
 * @param $xml PHP SimpleXML object containing OSM data
 * @param $conflate if true, try to join this data with existing POIs, if false, just create a new POI
 * @returns the POI PHP class object created in either case
 */
function goodNodeToPOI($xml, $conflate=FALSE) {
  global $ogcbaseuri, $osmbaseurl, $osmdataurl, $osmweburl, $category_scheme, $badcategories, $descriptioncategories;
  global $matchednodes; // from osm.load.php to count matches
  global $licenseidopenstreetmap, $iana;
  
  $poi = null;
  
  if ( empty($xml->attributes()->lat) || empty($xml->attributes()->lon) ) return NULL;
  
  // build POI properties
  $name = null;
  $categories = array();
  $description = null;
  
  $tags = $xml->tag; // array of <tag> elements
  foreach ( $tags as $tag ) {
    $k = (string)$tag['k'];
    $v = (string)$tag['v'];
    
    if ( $k == 'name' ) {
      $name = $v;
    
    } else if ( !(array_search($k, $descriptioncategories) === FALSE) ) {
      $description = new POIBaseType('DESCRIPTION');
      $description->setValue($v);
      $description->setAuthor( getOSMAuthor() );
      $description->setLicense( getOSMLicense() );

    } else {
      if ( array_search($k, $badcategories) === FALSE && strpos($k, 'note') !== 0 ) {
        $c = new POITermType('CATEGORY', $k, $v, $category_scheme . '#' . $k);
        $c->setAuthor( getOSMAuthor() );
        $c->setLicense( getOSMLicense() );
        $categories[] = $c;
      }
    }
  }
  
  // IF THERE'S NO NAME, IT'S NOT IMPORTANT ENOUGH TO STORE!!
  if ( empty($name) ) return NULL;
  
	if ( $conflate ) {
	  // get distance matches
	  $maxdistance = 200; // meters
	  $lat = (double)$xml->attributes()->lat;
	  $lon = (double)$xml->attributes()->lon;  

	  $matches = getDistanceMatches($lon, $lat, $maxdistance, 9);
	  // echo "number of features within $maxdistance meters of $name: " . sizeof($matches) . "\n";

	  // add name match scores to distance matches
	  $matches = getPOINames($name, $matches, FALSE);
	  // echo "num name matches: " . sizeof($matches) . "\n";

	  // select a top match and load that POI
	  $thematch = NULL;
	  if ( $matches != NULL && sizeof($matches) > 0 ) {
	    foreach($matches as $m) {
	      $m->computeScore();
	      // echo "Match score is: $m->score\n";
	    }
	    foreach($matches as $m) {
	      if ( ($thematch == NULL || $m->score > $thematch->score) && $m->poiuuid != NULL ) {
	        $thematch = $m;
	      }
	    }
	  }
	  if ( $thematch != NULL && $thematch->score < 0.2000 ) {
	    $poi = POI::loadPOIUUID($thematch->poiuuid);
	    $id = $poi->getMyId();
	    echo "\nGOT A MATCH!!!!!!!!\n";
	    echo "OSM POI: $name, lat: $lat, lon: $lon, ID: $id\n";
	    echo "matched: ";
	    foreach ($thematch->labels as $label=>$score) {
	      echo ($label . ">> dist score: " . $thematch->distscore);
	      echo (" name score: " . $score . " total score: " . $thematch->score . "\n");
	    }
	    $matchednodes++;
	  }		
	}

  
  // if there was no match, create a NEW POI
  if ( $poi == null ) {
    $poi = new POI( gen_uuid(), $ogcbaseuri);
    $poi->changed = TRUE;

    // if there was no match, then location is needed
    $loc = new Location();

    // points
    $poslist = (string)$xml->attributes()->lat . ' ' . (string)$xml->attributes()->lon;
    $geom = new Geom('point', 'Point', $poslist, 'centroid');
    // $geom->setBase($osmdataurl);
    // $geom->setId($osmid);
    $geom->author = getOSMAuthor();
    $geom->setLicense( getOSMLicense() );
    $loc->addPointGeom($geom);
    $poi->location = $loc;
  }

	// if we are conflating, add the OSM location as an alternate
  // points
  $poslist = (string)$xml->attributes()->lat . ' ' . (string)$xml->attributes()->lon;
  $geom = new Geom('point', 'Point', $poslist, 'osm');
  $geom->author = getOSMAuthor();
  $geom->setLicense( getOSMLicense() );
  $poi->location->addPointGeom($geom);
  
  // if there's no label, or no exact label match, then add a new label
  $labelmatch = false;
  $term = 'primary';
  if ( !empty($poi->labels) && sizeof($poi->labels)>0 ) {
    $term = 'secondary';
    foreach ($poi->labels as $pl) {
      $plname = $pl->getValue();
      if ( trim($plname) == trim($name) ) {
        $labelmatch = true;
      }
    }
  }
  if ( !$labelmatch ) {
    $l = new POITermType('LABEL', $term, $name, NULL);
    $l->setAuthor( getOSMAuthor() );
    $l->setLicense( getOSMLicense() );
    $poi->updatePOIProperty($l);
  }
  
  // add the description if we found one
  if ( !empty($description) ) {
    $poi->updatePOIProperty($description);
  }
  
  // create an OSM link object and add it to the POI
  $osmid = (string)$xml->attributes()->id;
  $l = new POITermType('LINK', 'related', NULL, $iana);
  $l->setBase($osmbaseurl);
  $l->setId($osmid);
  $l->setHref($osmdataurl . '/' . $osmid);
  $x = $poi->updatePOIProperty($l);
  
  // now add all categories
  foreach ( $categories as $cat ) {
    $poi->updatePOIProperty($cat);
  }
  
  return $poi;
}

?>