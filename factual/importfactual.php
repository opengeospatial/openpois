<?php
require_once("utils.php");
require_once("class.poi.php");
require_once("String-Similarity.php");
require_once("Factual.php");

// ex: http://api.v3.factual.com/t/global?KEY=s0pncs35mS6iHWIBQIxGFF6VbiezpKgPsNYD4uuF&geo={%22$circle%22:{%22$center%22:[34.06018,%20-118.41835],%22$meters%22:500}}

// $api = 'http://api.v3.factual.com/t/global?';
// Set your client key and secret
$minscore = 0.75;
$radius = 50;
$client_key = "s0pncs35mS6iHWIBQIxGFF6VbiezpKgPsNYD4uuF";
$client_secret = "0B9pKvYgSC7P7aKPHBJGxFQQdmFnInPK9rfgoekf";
$factual = new Factual($client_key,$client_secret);

// foreach ($d as $item) {
//   echo "name: " . $item['name'] . ", lat: " . $item['latitude'] . ", lon: " . $item['longitude'] , "\n";
// }

try {
  // cambridge bbox: NE 42.409809, -71.058884 and SW 42.339588, -71.167030
  // $sql = "select poiuuid, label, ST_X(geompt), ST_Y(geompt) from minipoi WHERE geompt && ST_SetSRID(ST_MakeBox2D(ST_Point(-71.167030,42.339588),ST_Point(-71.058884,42.409809)),4326)";
  // boston north topoquad: NE 42.5, -71 and SW 42.375, -71.125
  // boston south topoquad: NE 42.375, -71 and SW 42.25, -71.125
  $sql = "select poiuuid, label, ST_X(geompt), ST_Y(geompt) from minipoi WHERE geompt && ST_SetSRID(ST_MakeBox2D(ST_Point(-71.125,42.25),ST_Point(-71.0,42.375)),4326)";
  // $sql .= " LIMIT 22";
  $pgconn = getDBConnection();
  $c = $pgconn->query($sql);
  if ( $c ) {
    echo "PROCESSING " . $c->rowCount() . " POIs...\n";
    
    foreach ($c as $row) {
      static $counter = 0;
      $counter ++;
      echo "processing POI $counter...\n";
      
      // wait 1 seconds
      sleep(1);
      
      // load this POI
      $poiuuid = $row['poiuuid'];
      $poi = POI::loadPOIUUID($poiuuid);
      
      // Prepare parameters
      $lat = $row['st_y'];
      $lon = $row['st_x'];
      $poiname = $row['label'];

      // Perform the query to Factual
      $query = new FactualQuery;
      $query->within(new FactualCircle($lat, $lon, $radius));
      $res = $factual->fetch("places", $query);
      $d = $res->getData();

      // go on to next row if no places found
      if ( $d == null || sizeof($d) < 1 ) {
        echo "No POIs within 50 meters of OpenPOI $poiname\n";
        continue;
      }

      // find a good name match. Factual results are ordered by distance, 
      // so we are implicitly testing nearer POIs first -- so no need for a distance score. 
      // If an early one is a good name match, use it and continue
      foreach ($d as $item) {
        $fid = $item['factual_id'];
        if ( empty($fid) ) continue;
        $fname = $item['name'];
        if ( empty($fname) ) continue;
        
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
            if ( !isset($item['url']) ) continue;
            // insert link record for Factual item
            $link = new POITermType('LINK', 'related');
            // $link->setScheme('http://www.iana.org/assignments/link-relations/link-relations.xml');
            $link->setValue($item['namespace']);
            $link->setId($item['namespace_id']);
            $link->setHref($item['url']);
            echo "adding " . $item['url'] . "\n";
            $poi->updateSource($link);
          }

          continue;
        }
      } // end for each response to factual distance query
    } // end foreach row in poi search
  } // end if $c
} catch (Exception $e) {
  echo "Factual QUERY FAIL: " . $e->getMessage() . "\n";
}

$pgconn = null;

?>