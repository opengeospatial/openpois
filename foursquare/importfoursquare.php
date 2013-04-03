<?php 
require_once("FoursquareAPI.class.php");
require_once("../utils.php");

// Set your client key and secret
$client_key = "MX3RU5IA4JEDSZKODETXEQTSAHGX4KD4JRRDIEOVHDRFEMKG";
$client_secret = "NS03YSQGT0AQYU1SA5R5OP1WHW0ZTZMVQWUZVUINUSYS2GRN";

// Load the Foursquare API library
$foursquare = new FoursquareAPI($client_key,$client_secret);

date_default_timezone_set('America/New_York');
$dt = date('Ymd');

try {
  // cambridge bbox: NE 42.409809, -71.058884 and SW 42.339588, -71.167030
  // $sql = "select poiuuid, label, ST_X(geompt), ST_Y(geompt) from minipoi WHERE geompt && ST_SetSRID(ST_MakeBox2D(ST_Point(-71.167030,42.339588),ST_Point(-71.058884,42.409809)),4326)";
  // boston north topoquad: NE 42.5, -71 and SW 42.375, -71.125
  $sql = "select poiuuid, label, ST_X(geompt), ST_Y(geompt) from minipoi WHERE geompt && ST_SetSRID(ST_MakeBox2D(ST_Point(-71.125,42.375),ST_Point(-71.0,42.5)),4326)";
  $pgconn = getDBConnection();
  $c = $pgconn->query($sql);
  if ( $c ) {
    foreach ($c as $row) {
      // load this POI
      $poi = POI::loadPOIUUID($row['poiuuid']);
      
      // Prepare parameters
      $lat = $row['st_y'];
      $lon = $row['st_x'];
      $place = $row['label'];
      $params = array("intent"=>"match", "ll"=>"$lat,$lon", "query"=>$place, "v"=>$dt);
      
      // Perform a request to a public resource
      $response = $foursquare->GetPublic("venues/search",$params);
      // echo "\n\nres: $response\n\n";
      $venues = json_decode($response);

      // go on to next row if no venues matched
      if ( !isset($venues->response->venues) || sizeof($venues->response->venues)<1 ) continue;

      // get the venue id and query the venue for links
      foreach ($venues->response->venues as $venue) {
        // get venue id
        // echo "name: $venue->name\n";
        $vid = $venue->id;
        if ( empty($vid) ) continue;
        
        echo "Found a Foursquare match for $place: $vid (poiuuid: " . $row['poiuuid'] . ")\n";
        
        // insert link record for Foursquare item
        $link = new POITermType('LINK', 'related');
        $link->setBase('https://api.foursquare.com/v2/venues');
        $link->setId($vid);
        $link->setType('application/json');
        $link->setHref($link->getBase() . '/' . $vid);
        $link->setScheme('http://www.iana.org/assignments/link-relations/link-relations.xml');
        if ( $poi->updateSource($link) ) {
          logToDB("Foursquare added link id " . $vid . " to " . $poi->getId(), "UPDATEINFO");
        }
        
        // query for links
        $params = array("v"=>$dt);
        $response = $foursquare->GetPublic("venues/$vid/links",$params);
        $venuelinks = json_decode($response);
        
        if ( $venuelinks->response->links->count > 0 ) {
          // add links to POI
          foreach ($venuelinks->response->links->items as $item) {
            $base = 'http://www.' . $item->provider->id . '.com';
            $id = $item->linkedId;
            if ( isset($item->url) )
              $href = $item->url;
            
            $link = new POITermType('LINK', 'related');
            $link->setBase($base);
            $link->setId($id);
            $link->setHref($href);
            if ( strpos($base, 'menupages') !== FALSE || strpos($base, 'allmenus') !== FALSE ) {
              $link->setType('text/html');
            }
            $link->setScheme('http://www.iana.org/assignments/link-relations/link-relations.xml');
            if ( $poi->updateSource($link) ) {
              logToDB("Foursquare adding link from " . $item->provider->id . " with link " . $id . " to " . $poi->getId(), "UPDATEINFO");
            }
          }
        } // end links
      } // end for each venue
    } // end foreach row in query
  } // end if $c
} catch (Exception $e) {
  echo "Foursquare QUERY FAIL: " . $e->getMessage() . "\n";
}

$pgconn = null;

?>