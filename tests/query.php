<?php
include_once('utils.php');
include_once('class.poi.php');

parse_str($_SERVER['QUERY_STRING'], $q);
$query = array_change_key_case($q, CASE_LOWER);


//$request['bbox'] = '-70.2,41.2,-70.1,41.30';

if ( isset($query['bbox']) && !empty($query['bbox']) ) {
	  $coords = explode(",", $query['bbox']);
	  if ( sizeof($coords) != 4 ) {
	    echo "bbox parameter must consist of 4 comma-separated coordinates in this order: left,lower,right,upper";
	  }
	  
    // $bbox = "ST_MakeBox2D( (ST_Point($coords[1],$coords[0]), ST_Point($coords[3],$coords[2]) ) )";
    // $bbox = "ST_SetSRID(" . $bbox . "),4326)";
	  $bbox = "ST_SetSRID('BOX3D($coords[1] $coords[0],$coords[3] $coords[2])'::box3d,4326)";
	  $sql = "SELECT parentid from geo WHERE geompt && $bbox AND deleted IS NULL";
	  
	  // connect to db
    $conn = null;
    try {
      $conn = new PDO('pgsql:host=localhost;dbname=poidb', 'poidbadmin', 'genjisan');
      $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
      $conn->setAttribute(PDO::ATTR_ORACLE_NULLS, PDO::NULL_EMPTY_STRING);
    } catch (PDOException $e) {
      return "Error connecting: " . $e->getMessage() . "\n";
    }
	  
	  // run sql and loop through results to find poi id 
	  $poiuuids = array();
	  $rows = $conn->query($sql);
	  foreach ($rows as $row) {
	    $pid = getGeomPOIUUID($row, $conn);
	    if ( !empty($pid) ) {
	      $poiuuids[] = $pid;
	    }
	  }	  
	  
	  // use poi id to build pois, and return them as XML
	  header("Content-type: text/xml");
    
    if ( sizeof($poiuuids) < 1 ) {
      echo '<message type="error"><value>No POIs found in bounding box: ' . $query['bbox'] . '</value></message>\n';
    } else {
      if ( sizeof($poiuuids) > 1 ) {
        echo "<pois>\n";
      }
  	  foreach ($poiuuids as $poiuuid) {
  	    $p = POI::loadPOIUUID($poiuuid);
  	    if ( $p ) 
  	      echo $p->asXML();
  	  }
      if ( sizeof($poiuuids) > 1 ) {
        echo "</pois>\n";
      }
    }
} else {
  echo "bbox parameter must exist and consist of 4 comma-separated coordinates in this order: left,lower,right,upper";
}

?>