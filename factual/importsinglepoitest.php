<?php
require_once("utils.php");
require_once("class.poi.php");
require_once("String-Similarity.php");
require_once("Factual.php");

$minscore = 0.75;
$client_key = "s0pncs35mS6iHWIBQIxGFF6VbiezpKgPsNYD4uuF";
$client_secret = "0B9pKvYgSC7P7aKPHBJGxFQQdmFnInPK9rfgoekf";
$factual = new Factual($client_key,$client_secret);

// load this POI
$poiuuid = '02207dbe-31ee-4c29-9b32-1ed580de4282';
$poi = POI::loadPOIUUID($poiuuid);

// Prepare parameters
$pt = $poi->location->getFirstPoint();
$cs = explode(' ', $pt->poslist);
$lat = $cs[0];
$lon = $cs[1];
$poiname = $poi->labels[0]->getValue(); //'Craigie on Main';
// die("lat: $lat, lon: $lon\n");

// Perform the query to Factual
$query = new FactualQuery;
$query->within(new FactualCircle($lat, $lon, 50));
$res = $factual->fetch("places", $query);
$d = $res->getData();

// go on to next row if no places found
if ( $d == null || sizeof($d) < 1 ) {
  die("No POIs within 50 meters of OpenPOI: $poiname\n");
}

// find a good name match. Factual results are ordered by distance, 
// so we are implicitly testing nearer POIs first -- so no need for a distance score. 
// If an early one is a good name match, use it and continue
foreach ($d as $item) {
  $fid = $item['factual_id'];
  if ( empty($fid) ) continue;
  $fname = $item['name'];
  if ( empty($fname) ) continue;
  echo "trying to conflate POI $poiname with Factual $fname...\n";
  
  $comp = new StringMatch;
  $result = $comp->fstrcmp($poiname, strlen($poiname), $fname, strlen($fname), $minscore);
  if ( $result >= $minscore ) {
    echo "Found a Factual match for $poiname: $fname (poiuuid: " . $poiuuid . ")\n";

    // insert link record for Factual item
    $link = new POITermType('LINK', 'related');
    // $link->setScheme('http://www.iana.org/assignments/link-relations/link-relations.xml');
    $link->setId('http://www.factual.com/' . $fid);
    $link->setHref($link->getId() . '.json');
    $link->setType('application/json');
    $poi->updateSource($link);
    
    // insert categories for Factual item
    if ( isset($item['category']) ) {
      $cats = explode('>', $item['category']);
      foreach ($cats as $cat) {
        $ca = new POITermType('CATEGORY', 'tag');
        $ca->setValue( trim($cat) );
        $ca->setAuthor( getFactualAuthor() );
        $poi->updatePOITermTypeProperty($ca);
      }
    }
    
    // query for crosswalk links
    $cqquery = new CrosswalkQuery();
    $cqquery->factualID($fid);
    $cqres = $factual->fetch("places", $cqquery);
    $d = $cqres->getData();
    // go on to next row if no places found
    if ( $d == null || sizeof($d) < 1 ) continue;
    foreach ( $d as $item ) {
      // insert link record for Factual item
      $link = new POITermType('LINK', 'related');
      // $link->setScheme('http://www.iana.org/assignments/link-relations/link-relations.xml');
      $link->setValue($item['namespace']);
      $link->setId($item['namespace_id']);
      $link->setHref($item['url']);
      echo "adding " . $item['url'] . "\n";
      $poi->updateSource($link);
    }
  } // end for each response to factual distance query
}
?>