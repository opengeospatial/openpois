<?php
include_once('class.poi.php');
include_once('utils.php');

// boston north topoquad: NE 42.5, -71 and SW 42.375, -71.125
// boston south topoquad: NE 42.375, -71 and SW 42.25, -71.125
$bbox = "-71.125,42.25,-71,42.5";
$pois = queryByBBox($bbox);
// foreach ($pois as $poi) {
//   echo $poi->asJSON();
// }



function queryByBBox($bbox) {
  // build points for bbox query
  // coords are XY: 0 left, 1 is lower, 2 is right, 3 is upper
  // so no need to reverse to XY for PostGIS
  $cs = explode(',', $bbox);
  $leftlowpt = 'ST_Point(' . $cs[0] . ',' . $cs[1] . ')';
  $rightuppt = 'ST_Point(' . $cs[2] . ',' . $cs[3] . ')';
  $sql = "select poiuuid, label from minipoi";
  $sql .= " WHERE geompt && ST_SetSRID(ST_MakeBox2D($leftlowpt,$rightuppt),4326)";
  
  // $pois = array();
  $conn = getDBConnection();
  $c = $conn->query($sql);      
  if ( $c ) {
    foreach($c as $row) {
      // $pois[] = POI::LoadPOIUUID($row['poiuuid']);
      $poi = POI::LoadPOIUUID($row['poiuuid']);
      echo $poi->asJSON();
      echo "\n";
    }
  }
  
  // return $pois;
}

?>