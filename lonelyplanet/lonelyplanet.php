<?php
include_once('class.matchcandidate.php');
include_once('String-Similarity.php');
include_once('utils.php');
include_once('class.poibasetype.php');
include_once('class.poitermtype.php');
include_once('class.poi.php');

// $name = "Faneuil Hall";
// $lon = '-71.056868';
// $lat = '42.360583';
$lonelyplanetup = 'jXEee9MsPwAfgAQYMkLO2A:EAPCgR3QBDMdaeGNVwkraBPpu6E9je55JaIjNhBEWE';
$lonelyplanetapi = 'api.lonelyplanet.com/api';

// test vars
// $pois = POI::loadPOIsByLabel("Harvard Square");
// $pois = POI::loadPOIsByLabel("Faneuil Hall / Union St");
// if ( sizeof($pois)<1 ) {
//   echo "No POIs found.\n";
//   exit;
// }
// $poi = $pois[0];
// echo $poi->asXML(false, false);

// $poi = linkLonelyPlanet($poi);

// make the LP match into POI format and return that
// $lppoi = makeLonelyPlanetPOI($lpid);

function linkLonelyPlanet($poi) {
  global $lonelyplanetapi;
  
  if ( checkLonelyPlanetLinked($poi) ) { // we're already done
    return $poi; 
  }
  
  $name = $poi->labels[0]->getValue();

  // $lon = '-71.1189455';
  // $lat = '42.373393';
  $coords = $poi->location->getFirstPoint()->getPosList();
  $cs = explode(' ', $coords);
  $lat = $cs[0];
  $lon = $cs[1];

  // get a good match from LP
  $lpid = findPOILonelyPlanet($name, $lat, $lon);
  $poimyid = $poi->getMyId();

  if ( !empty($lpid) ) {
    logToDB("POI myid $poimyid linked to LP ID: $lpid", 'LINKINFO');

    // add the LP match to our poi database
    $link = new POITermType('LINK', 'alternate');
    $link->setHref("http://$lonelyplanetapi/pois/$lpid");
    $link->setId($link->getHref());
    $link->setType('text/xml');
    $poi->updateSource($link);
  } else {
    logToDB("No LP link for POI myid $poimyid", 'LINKINFO');
  }
  
  return $poi;
}

function checkLonelyPlanetLinked($poi) {
  global $lonelyplanetapi;

  foreach ($poi->links as $link) {
    $href = $link->getHref();
    if ( strpos($href, "http://$lonelyplanetapi/pois/") === 0 ) {
      return TRUE;
    }
  }
  return FALSE;
}

/**
 * UNFINISHED!!!!
 */
function makeLonelyPlanetPOI($lpid) {
  global $lonelyplanetup, $lonelyplanetapi;

  // $q = "http://$lonelyplanetup@$lonelyplanetapi/pois/$lpid";
  $q = "/Users/rajsingh/workspace/openpoidb/application/lonelyplanet/example_faneuilhallresponse.xml";
  $xml = simplexml_load_file($q);
  var_dump($xml);
}

/**
 * Creates a bounding box around the input lat/lon of 100 meters, uses this to query 
 * Lonely Planet for POIs, then computes a match score based on name similarity and 
 * distance from the input name/lat/lon. 
 * Returns a URL to the fetch the best match (in XML)
 */
function findPOILonelyPlanet($name, $lat, $lon) {
  global $lonelyplanetup, $lonelyplanetapi;

  $bbox = buildBBox($lat, $lon, 100);
  $q = "http://$lonelyplanetup@$lonelyplanetapi/bounding_boxes/";
  $q .= $bbox[3].','.$bbox[1].','.$bbox[2].','.$bbox[0].'/pois';
  // $q = "/Users/rajsingh/workspace/openpoidb/application/lonelyplanet/example_bbox_faneuil_res.xml";
  // echo "lp url: $q";
  $xml = simplexml_load_file($q);
  
  if ( empty($xml->poi) ) {
    return NULL;
  }

  $comp = new StringMatch;
  $matches = array();
  foreach ($xml->poi as $lppoi) {
    $n = (string)$lppoi->name;

    $lscore = $comp->fstrcmp($name, strlen($name), $n, strlen($n), 0.5);
    // echo "score for $n is: $lscore\n";
    if ( $lscore > 0.5 ) {
      $m = new MatchCandidate($lppoi->id);
      $m->labels = array($n => $lscore);

      $blon = floatval($lppoi->{'digital-longitude'});
      $blat = floatval($lppoi->{'digital-latitude'});
      $dist = ddDistance($lat, $lon, $blat, $blon);
      $m->dist = $dist;

      $m->computeScore(500);
      $matches[] = $m;
    }
  }

  // get best match
  $highscore = 0.0;
  $bm = null;
  foreach ($matches as $m) {
    if ( $m->score > $highscore ) {
      $highscore = $m->score;
      $bm = $m;
    }
    // echo "id: $m->geouuid\tdistance: $m->dist\tscore: $m->score\n";
    // foreach($m->labels as $l => $s) {
    //   echo "label: $l\tscore: $s\n";
    // }
  }
  
  if ( !empty($bm) ) 
    return (string)$bm->geouuid;
  else 
    return NULL;
}

?>