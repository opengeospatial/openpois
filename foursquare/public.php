<?php 
require_once("FoursquareAPI.class.php");
require_once("../utils.php");

// Set your client key and secret
$client_key = "MX3RU5IA4JEDSZKODETXEQTSAHGX4KD4JRRDIEOVHDRFEMKG";
$client_secret = "NS03YSQGT0AQYU1SA5R5OP1WHW0ZTZMVQWUZVUINUSYS2GRN";

// Load the Foursquare API library
$foursquare = new FoursquareAPI($client_key,$client_secret);

// Prepare parameters
date_default_timezone_set('America/New_York');
$dt = date('Ymd');
$lat = '42.362777';
$lon = '-71.087927';
$place = 'Legals Seafood';
$params = array("intent"=>"match", "ll"=>"$lat,$lon", "query"=>$place, "v"=>$dt);

// Perform a request to a public resource
$response = $foursquare->GetPublic("venues/search",$params);
echo "\n\nres: $response\n\n";
$venues = json_decode($response);

foreach ($venues->response->venues as $venue) {
  echo "$venue->name\n";
  echo "$venue->id\n";
}

try {
  // cambridge bbox
  // NE 42.409809, -71.058884
  // SW 42.339588, -71.167030
  $sql = "select label, ST_X(geompt), ST_Y(geompt) from minipoi WHERE geompt && ST_SetSRID(ST_MakeBox2D(ST_Point(-71.167030,42.339588),ST_Point(-71.058884,42.409809)),4326) LIMIT 3";
  $pgconn = getDBConnection();
  $c = $conn->query($sql);
  if ( $c ) {
    foreach ($c as $row) { 
      
      $poi = new POI("http://dummyid.example.com", null);
      $poi = parent::loadDBData($row, $conn, $poi);
    }
  }
} catch (Exception $e) {
  echo "Foursquare QUERY FAIL: " . $e->getMessage() . "\n";
}

$pgconn = null;

?>