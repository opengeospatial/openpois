<?php
require_once('constants.php');
require_once('conflate.php');
require_once('class.poitermtype.php');

/**
 * argv[0] program name
 * argv[1] resource
 * argv[2] coords
 */
$dbpedia_data_url = 'http://dbpedia.org/data/';
$st = strlen('http://dbpedia.org/resource/');
echo "resource: " . $argv[1]."\tcoords: ".$argv[2]."\n";

if ( $poi = buildPOI($argv[1], $argv[2]) ) {
  // echo $poi->asXML() . "\n";
  conflate($poi);
}

function conflate($poi) {
  $maxdistance = 500;
  $name = $poi->labels[0]->getValue();
  $g = $poi->getLocation()->getFirstPoint();
  $cs = $g->getPosList();
  $cs = explode(" ", $cs);
  
  $matches = getDistanceMatches($cs[1], $cs[0], $maxdistance, 9);
  echo "number of features within $maxdistance meters of $name: " . sizeof($matches) . "\n";
  
  // add name match scores to distance matches
  if ( $matches != NULL && sizeof($matches) > 0 ) {
      $matches = getPOINames($name, $matches, FALSE);
      echo "num name matches: " . sizeof($matches) . "\n";
    }
  
  // select a top match and load that POI
  $thematch = NULL;
  if ( $matches != NULL && sizeof($matches) > 0 ) {
    foreach($matches as $m) {
      $m->computeScore();
      echo "Match score is: $m->score\n";
    }
    foreach($matches as $m) {
      if ( ($thematch == NULL || $m->score > $thematch->score) && $m->poiuuid != NULL ) {
        $thematch = $m;
        echo "The match POIUUID is: " . $m->poiuuid . "\n";
      }
    }
  }
  $poix = NULL;
  if ( $thematch != NULL && $thematch->score < 0.2000 ) { // 80% match
    $poix = POI::loadPOIUUID($thematch->poiuuid);
    echo "\nGOT A MATCH!!!!!!!!\n";
  }

  // if no conflation match, write it and go
  if ( $poix == NULL ) {
    echo "adding new POI\n";
    $poi->updateDB();
    return;
  }
  
  // join the POIs if there was a match
  $poix->location->updateGNPoint( $poi->location->getFirstPoint() );
  $poi->labels[0]->setTerm('secondary');
  $poix->updatePOITermTypeProperty( $poi->labels[0] );
  $poix->updatePOIBaseTypeProperty( $poi->descriptions[0] );
  foreach ($poi->links as $ls) {
    $poix->updatePOITermTypeProperty($ls);
  }
  foreach ($poi->categories as $cs) {
    $poix->updatePOITermTypeProperty($cs);
  }
  $poix->updateDB();
  
}

/**
 * Take a resource and a coordinate string and build a POI.
 * Get label, description, links, and categories from the resource's JSON representation.
 */
function buildPOI($resource, $poslist) {
  global $dbpedia_data_url, $st, $ogcbaseuri, $iana;
  
  $poi = new POI( gen_uuid(), $ogcbaseuri);
  $loc = new Location();

  // points
  $geom = new Geom('point', 'Point', $poslist, 'centroid');
  $geom->author = getDBPediaAuthor();
  $loc->addPointGeom($geom);
  $poi->location = $loc;
  
  // LINK to dbpedia
  $l = POITermType::getRelatedLink();
  $l->setId($resource);
  $l->setHref($resource);
  $l->setAuthor( getDBPediaAuthor() );
  $poi->addLink($l);
    
  /* get JSON representation for more data */
  $n = substr($resource, $st);
  $json_url = $dbpedia_data_url . $n . '.json';
  // $json_url = 'file:///Users/rajsingh/Desktop/Aegean_Sea.json';
  echo "Getting data from $json_url...\n";
  $json = curl_get_file_contents($json_url);
  // echo "JSON:\n$json\n";
  if ( $json == null || !$json || strlen($json)<100 ) {
    echo "Empty JSON data:\n$json\nSkipping $resource\n";
    return false;
  }
  
  $r = json_decode($json, true);
  if ( $r == null ) {
    echo "Empty JSON PHP object. Skipping $resource\n";
    return false;
  }
  
  foreach ($r as $key=>$val) {
    if ( $key === $resource ) {
      if ( is_array($val) ) {
        foreach ($val as $k2=>$v2) {
          // Get the label
          if ( $k2 === "http://www.w3.org/2000/01/rdf-schema#label" ) {
            $n = getValue($v2, 'en');
            $l = new POITermType('LABEL', 'primary', $n, NULL);
            $l->setAuthor( getDBPediaAuthor() );
            $poi->updatePOITermTypeProperty($l);

            // Get links to related web resources
          } elseif ( $k2 === "http://www.w3.org/2002/07/owl#sameAs" ) {
            foreach ($v2 as $k3=>$v3) {
              $n = $v3['value'];
              $l = new POITermType('LINK', 'related', NULL,  $iana);
              $l->setHref($n);
              $l->setAuthor( getDBPediaAuthor() );
              $poi->updatePOITermTypeProperty($l);
            }

            // Get the description
          } elseif ( $k2 === "http://www.w3.org/2000/01/rdf-schema#comment" ) {
            $n = getValue($v2, 'en');
            $description = new POIBaseType('DESCRIPTION');
            $description->setValue($n);
            $description->setAuthor( getDBPediaAuthor() );
            $poi->updatePOIBaseTypeProperty($description);

            // Get categories
          } elseif ( $k2 === "http://purl.org/dc/terms/subject" ) {
            foreach ($v2 as $k3=>$v3) {
              $n = $v3['value'];
              $ns = explode(":", $n);
              $n = $ns[2];
              $l = new POITermType('CATEGORY', $n, NULL, 'http://dbpedia.org/resource/Category');
              $l->setAuthor( getDBPediaAuthor() );
              $poi->updatePOITermTypeProperty($l);
            }
          }
        }      
      }
    }
  }

  return $poi;
} // end buildPOI

function getValue($arr, $lang) {
  foreach ( $arr as $k=>$v ) {
    if ( $v['lang'] === $lang ) {
      return $v['value'];
    }
  }
  return false;
}

?>