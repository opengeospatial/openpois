<?php
include_once('constants.php');
include_once('class.poi.php');

$pgconn = null;
// $pgconnalt = null;

function getDBPediaAuthorID() {
  global $authoriddbpedia;
  return $authoriddbpedia;
}
function getDBPediaAuthor() {
  $o = new POITermType('AUTHOR', 'publisher');
  $o->setId('http://dbpedia.org');
  $o->setValue('DBPedia');
  $o->myid = getDBPediaAuthorID();
  return $o;
}

function getFutouringLicenseID() {
  global $licenseidfutouring;
  return $licenseidfutouring;
}
function getFutouringLicense() {
  $lic = new POITermType('LICENSE', 'CC-BY-SA', NULL, 'http://creativecommons.org/licenses/by-sa/3.0/');
  $lic->myid = getFutouringLicenseID();
  $lic->href = 'http://creativecommons.org/licenses/by-sa/3.0/';
  return $lic;
}
function getFutouringAuthorID() {
  global $authoridfutouring;
  return $authoridfutouring;
}
function getFutouringAuthor() {
  $o = new POITermType('AUTHOR', 'publisher');
  $o->setId('http://www.futouring.com');
  $o->myid = getFutouringAuthorID();
  return $o;
}

function getOSMLicenseID() {
  global $licenseidopenstreetmap;
  return $licenseidopenstreetmap;
}
function getOSMLicense() {
  $lic = new POITermType('LICENSE', 'odbl');
  $lic->setId( getOSMLicenseID() );
  $lic->setHref('http://opendatacommons.org/licenses/odbl/');
  $lic->setValue('ODBL');
  $lic->setType('text/html');
  $lic->myid = getOSMLicenseID();
  return $lic;
}

function getOSMAuthorID() {
  global $authoridopenstreetmap;
  return $authoridopenstreetmap;
}
function getOSMAuthor() {
  $o = new POITermType('AUTHOR', 'publisher');
  $o->setId('http://openstreetmap.org');
  $o->setValue('OSM');
  $o->setType('text/plain');
  $o->myid = getOSMAuthorID();
  return $o;
}

function getGeonamesAuthorID() {
  global $authoridgeonames;
  return $authoridgeonames;
}
function getGeonamesAuthor() {
  $o = new POITermType('AUTHOR', 'publisher');
  $o->setId('http://geonames.org');
  $o->setValue('GeoNames');
  $o->myid = getGeonamesAuthorID();
  return $o;
}

function getCHGISMAuthorID() {
  global $authoridchgis;
  return $authoridchgis;
}
function getCHGISMAuthor() {
  $o = new POITermType('AUTHOR', 'editor');
  $o->setId('http://www.fas.harvard.edu/~chgis/');
  $o->setValue('CHGIS');
  $o->myid = getCHGISMAuthorID();
  return $o;
}

/**
 * Compute the distance between two points in decimal degrees
 */
function ddDistance($alat, $alon, $blat, $blon) {
  $dist_1d_lon_m = cos(deg2rad($alat)) * 111325;
  $dlon = abs($alon-$blon) * $dist_1d_lon_m;

  $dist_1d_lat_m = 111325;
  $dlat = abs($alat-$blat) * $dist_1d_lat_m;
  
  return sqrt( ($dlat*$dlat) + ($dlon*$dlon) );
}

function ddDistanceRough($lat, $dist=100) {
  $dist_1d_lon_m = cos(deg2rad($lat)) * 111325;
  $dist_distd_lon_m = $dist / $dist_1d_lon_m;
  return $dist_distd_lon_m;
}

// $lon = '-71.056868';
// $lat = '42.360583';
/** 
 * @TODO - MAKE THIS WORK PROPERLY!!!!!
 * Construct a bounding box around the point $lat, $lon (YX) with a 
 * width of ($dist * 2) meters. 
 * @return array of 4 items: minx, miny, maxx, maxy
 */
function buildBBox($lat, $lon, $dist=100) {
  $dist_1d_lon_m = cos(deg2rad($lat)) * 111325;
  $dist_distd_lon_m = $dist / $dist_1d_lon_m;
  $minx = $lon - $dist_distd_lon_m;
  $maxx = $lon + $dist_distd_lon_m;
  
  $dist_1d_lat_m = 111325;
  $dist_distd_lat_m = $dist / $dist_1d_lat_m;
  $miny = $lat - $dist_distd_lat_m;
  $maxy = $lat + $dist_distd_lat_m;
  
  $a = array($minx, $miny, $maxx, $maxy);
  return $a;

  $sql = "SELECT ST_Buffer( (ST_GeographyFromText('SRID=4326;POINT($lon $lat)')), $dist )";
  $pgconn = getDBConnection();
  $c = $pgconn->query($sql);
  if ( $c ) {
    foreach ( $c as $row) {
       $poiuuids[] = $row['poiuuid'];
    }
  }
}

function findPOITermTypeByProperty($objname, $property, $propval, $pgconn=NULL) {
  if ( empty($objname)||empty($property)||empty($propval) ) return FALSE;
  if ( empty($pgconn) ) $pgconn = getDBConnection();
  $sql = "SELECT * from poitermtype WHERE objname like '$objname' AND $property like '$propval'";
  $c = $pgconn->query($sql);
  if ( $c ) {
    foreach ( $c as $row ) {
      $poitt = POITermType::loadDBData($row, $pgconn);
      return $poitt;
    }
  }
  return FALSE;
}

function getPOIName($poiuuid) {
  try {
    // $sql = "SELECT value FROM poitermtype where parentid='$poiuuid' and deleted is NULL and objname like 'LABEL' and term like 'primary'";
		$sql = "SELECT label FROM minipoi WHERE poiuuid='$poiuuid'"; // faster than above

    $pgconn = getDBConnection();
    $c = $pgconn->query($sql);
    if ( $c ) {
      foreach ( $c as $row) {
         return $row['label']; //return $row['value'];
      }
    } else {
      return NULL;
    }
    
  } catch (Exception $e) {
    echo "findNearestPOIUUIDs() failed: " . $e->getMessage() . "\n";
    echo "$sql\n";
    return NULL;
  }
  return NULL;
}

/**
 * @param dist is distance in meters
 * @return array of match candidates: name, poi id, distance, score
 * NOTE: This only works if the data is in EPSG:4326
 */
function findNearestPOIUUIDs($lat, $lon, $dist=500, $limit=1) {
	if ( empty($lat) || empty($lon) ) return FALSE;
	$pgconn = NULL;
	$poiuuids = NULL;
	
  try {
    // $pt = "ST_GeographyFromText('SRID=4326;POINT($lon $lat)')";
    // $sql = "SELECT poiuuid, ST_Distance(Geography(ST_Transform(geompt,4326)), $pt) AS dist FROM minipoi";
    // $sql .= " WHERE ST_DWithin(Geography(ST_Transform(geompt,4326)), $pt, $dist)";
    // $sql .= " ORDER BY dist ASC LIMIT $limit";
    $pt = "ST_GeographyFromText('SRID=4326;POINT($lon $lat)')";
    $sql = "SELECT DISTINCT poiuuid, ST_Distance(geogpt, $pt, FALSE) AS dist FROM minipoi";
    $sql .= " WHERE ST_DWithin(geogpt, $pt, $dist, FALSE)";
    $sql .= " ORDER BY dist ASC LIMIT $limit";
    // $pt = "ST_GeometryFromText('SRID=4326;POINT($lon $lat)')";
    // $sql = "SELECT poiuuid, ST_Distance(geompt, $pt) AS dist FROM minipoi";
    // $dddist = ddDistanceRough($lat, $dist);
    // $sql .= " WHERE ST_DWithin(geompt, $pt, $dddist)";
    // $sql .= " ORDER BY dist ASC LIMIT $limit";
    // error_log("SQL: $sql");

    $pgconn = getDBConnection();
    $c = $pgconn->query($sql);
    if ( $c ) {
	    $poiuuids = array();
      foreach ( $c as $row) {
         $poiuuids[] = $row['poiuuid'];
      }
    }
    
		$pgconn = NULL;
  } catch (Exception $e) {
    echo "findNearestPOIUUIDs() failed: " . $e->getMessage() . "\n  >$sql\n";
    $poiuuids = NULL;
	}

	$pgconn = NULL;
  return $poiuuids;
}

/**
 * Add a POIBaseType or POITermType object to the POI by going directly to the database.
 * Only works with objects that are direct children of POI (except Location), 
 * so not Relationship or Geom.
 */
function insertPOIObjectFromUUID($poiobj, $poiuuid) {
  if ( !empty($poiuuid) ) {
    $poiobj->insertDB($poiuuid, getDBConnection());
  } else {
    logToDB("insertPOIObjectFromUUID: No active POI with UUID: $poiuuid");
  }
}

/**
 * Add a POIBaseType or POITermType object to the POI by going directly to the database.
 * Only works with objects that are direct children of POI (except Location), 
 * so not Relationship or Geom.
 */
function insertPOIObjectFromID($poiobj, $poiid) {
  $puid = POI::getPOIUUID($poiid);
  if ( !empty($puid) ) {
    $poiobj->insertDB($puid, getDBConnection());
  } else {
    echo "No active POI with ID: $poiid\n";
  }
}

function getDBAltConnection() {
  global $pgconnalt, $pghost, $dbnamealt, $dbadmin, $dbpw;
  
  if ( $pgconnalt != null ) {
    return $pgconnalt;
  }
  try {
    $pgconnalt = new PDO("pgsql:host=$pghost;dbname=$dbnamealt", $dbadmin, $dbpw);
    $pgconnalt->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pgconnalt->setAttribute(PDO::ATTR_ORACLE_NULLS, PDO::NULL_EMPTY_STRING);
    return $pgconnalt;
  } catch (PDOException $e) {
    echo "Error connecting: " . $e->getMessage() . "\n";
    return FALSE;
  }
}

function getDBConnection() {
  global $pgconn, $pghost, $dbname, $dbadmin, $dbpw;
  
  if ( $pgconn != null ) {
    return $pgconn;
  }  
  try {
    $pgconn = new PDO("pgsql:host=$pghost;dbname=$dbname", $dbadmin, $dbpw);
    $pgconn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pgconn->setAttribute(PDO::ATTR_ORACLE_NULLS, PDO::NULL_EMPTY_STRING);
    return $pgconn;
  } catch (PDOException $e) {
    echo "Error connecting: " . $e->getMessage() . "\n";
    return FALSE;
  }
}

/**
 * Given that the input is a POI location object, add assuming the 
 * first geometry object in that location object is a point, 
 * return that latitude and longitude in an array
 */
function getLatLon($location) {
  $geom = $location->getFirstPoint();
  if ( empty($geom) ) return null;
  $cs = explode(' ', $geom->getPosList());
  return $cs;
}

/**
 * Find the POI's ID of which the given Geometry object is a part.
 * @param database row representing a POI Geometry object $row
 * @param PostgreSQL database connection $pgconn
 * @return POI ID of the POI record
 */
function getGeomPOIUUID($row, $pgconn) {
  $sql = "SELECT parentid FROM location WHERE myid = '" . $row['parentid'] ."' AND deleted IS NULL";
  $rows = $pgconn->query($sql);
  if ( $rows ) {
    foreach ($rows as $row) { // should be only one
      return $row['parentid'];
    }
  } else {
    return FALSE;
  }
}

function getBaseURI($uri) {
  $u = '';
  $uparts = parse_url($uri);
  if ( isset($uparts['scheme']) ) {
    $u = $uparts['scheme'] . '://';
  } else {
    $u = 'http://';
  }
  
  $u .= $uparts['host'];
  
  if ( isset($uparts['port']) ) {
    $u .= ':' . $uparts['port'];
  }
  
  if ( strlen($u) > 0 ) {
    return $u;
  } else {
    return NULL;
  }
}

function validateURL($uri) {
  $v = "/^(http|https|ftp):\/\/([A-Z0-9][A-Z0-9_-]*(?:\.[A-Z0-9][A-Z0-9_-]*)+):?(\d+)?\/?/i";
  return (bool)preg_match($v, $uri);
}

/**
 * Create a universally unique ID for postgresql
 */
function gen_uuid() {
  return sprintf( '%04x%04x-%04x-%04x-%04x-%04x%04x%04x',
    // 32 bits for "time_low"
    mt_rand( 0, 0xffff ), mt_rand( 0, 0xffff ),
  
    // 16 bits for "time_mid"
    mt_rand( 0, 0xffff ),
  
    // 16 bits for "time_hi_and_version",
    // four most significant bits holds version number 4
    mt_rand( 0, 0x0fff ) | 0x4000,
  
    // 16 bits, 8 bits for "clk_seq_hi_res",
    // 8 bits for "clk_seq_low",
    // two most significant bits holds zero and one for variant DCE1.1
    mt_rand( 0, 0x3fff ) | 0x8000,
  
    // 48 bits for "node"
    mt_rand( 0, 0xffff ), mt_rand( 0, 0xffff ), mt_rand( 0, 0xffff )
  );
}

function echoPOIXML($p) {
  $dom = new DOMDocument('1.0', 'UTF-8');
  $dom->preserveWhiteSpace = false;
  $dom->loadXML($p->asXML());
  $dom->formatOutput = true;
  echo $dom->saveXML();
}

/**
 * User defined error handling function
 */
function userErrorHandler($errno, $errmsg, $filename, $linenum, $vars) {
  global $adminemail;
  global $openplaceserrorlog;
  
    // timestamp for the error entry
    $dt = date("Y-m-d H:i:s (T)");

    // define an assoc array of error string
    // in reality the only entries we should
    // consider are E_WARNING, E_NOTICE, E_USER_ERROR,
    // E_USER_WARNING and E_USER_NOTICE
    $errortype = array (
                E_ERROR              => 'Error',
                E_WARNING            => 'Warning',
                E_PARSE              => 'Parsing Error',
                E_NOTICE             => 'Notice',
                E_CORE_ERROR         => 'Core Error',
                E_CORE_WARNING       => 'Core Warning',
                E_COMPILE_ERROR      => 'Compile Error',
                E_COMPILE_WARNING    => 'Compile Warning',
                E_USER_ERROR         => 'User Error',
                E_USER_WARNING       => 'User Warning',
                E_USER_NOTICE        => 'User Notice',
                E_STRICT             => 'Runtime Notice',
                E_RECOVERABLE_ERROR  => 'Catchable Fatal Error'
                );
    // set of errors for which a var trace will be saved
    $user_errors = array(E_USER_ERROR, E_USER_WARNING, E_USER_NOTICE, E_RECOVERABLE_ERROR, E_WARNING);
    
    $err = "<errorentry>\n";
    $err .= "\t<datetime>" . $dt . "</datetime>\n";
    $err .= "\t<errornum>" . $errno . "</errornum>\n";
    $err .= "\t<errortype>" . $errortype[$errno] . "</errortype>\n";
    $err .= "\t<errormsg>" . $errmsg . "</errormsg>\n";
    $err .= "\t<scriptname>" . $filename . "</scriptname>\n";
    $err .= "\t<scriptlinenum>" . $linenum . "</scriptlinenum>\n";

    if (in_array($errno, $user_errors)) {
        $err .= "\t<vartrace>" . wddx_serialize_value($vars, "Variables") . "</vartrace>\n";
    }
    $err .= "</errorentry>\n\n";
    
    // for testing
    // echo $err;

    // save to the error log, and e-mail me if there is a critical user error
    error_log($err, 3, $openplaceserrorlog);
    if ($errno == E_USER_ERROR) {
        mail($adminemail, "Critical User Error", $err);
    }
}

function logToDB($msg, $type='ERROR') {
  $pgconn = getDBConnection() or die ("Unable to connect!");
  $msg = addslashes($msg);
  $sql = "INSERT INTO poilog (date, type, msg) VALUES(NOW(), '$type', '$msg')";
  try {
    $pgconn->exec($sql);
  } catch (Exception $e) {
    throw $e;
  }  
}

/**
 * This function transforms a text date into a PHP Date object. 
 * Acceptable input formats are YYYY, dd-mm-YYYY, and mm/dd/YYYY
 * Returns null if input can't be transformed into a date.
 */
function phpDate($date) {
  try {
    if ( strpos($date, '/') !== false ) {
      $s = explode('/', $date);
      if ( sizeof($s)<3 ) return null; // give up
      if ( checkdate($s[0], $s[1], $s[2]) ) {
        return (new DateTime($s[0].'/'.$s[1].'/'.$s[2]));
      } else {
        return null; // give up
      }
      
    } else if ( strpos($date, '-') !== false ) {
      $s = explode('-', $date);
      if ( sizeof($s)<3 ) return null; // give up
      if ( checkdate($s[1], $s[2], $s[0]) ) {
        return (new DateTime($s[0].'-'.$s[1].'-'.$s[2]));
      } else {
        return null; // give up
      }
      
    } else { // just a year given?
      $s = array(1, 1, $date);
      if ( checkdate(1, 1, $date) ) {
        return (new DateTime($date.'-1-1'));
      } else {
        return null; // give up
      }
    }
  } catch (Exception $e) {
    echo "Exception:\n$e\n";
    return null;
  }
}

function curl_get_file_contents($url) {
  $c = curl_init();
  curl_setopt($c, CURLOPT_RETURNTRANSFER, TRUE);
  curl_setopt($c, CURLOPT_HEADER, FALSE);
  curl_setopt($c, CURLOPT_FOLLOWLOCATION, TRUE);
  curl_setopt($c, CURLOPT_USERAGENT, "spider");
  curl_setopt($c, CURLOPT_AUTOREFERER, TRUE);
  curl_setopt($c, CURLOPT_CONNECTTIMEOUT, 120);
  curl_setopt($c, CURLOPT_TIMEOUT, 120);
  curl_setopt($c, CURLOPT_MAXREDIRS, 9);
  curl_setopt($c, CURLOPT_URL, $url);
  $contents = curl_exec($c);
  curl_close($c);

  if ($contents) return $contents;
      else return FALSE;
}

// Function written by Marcus L. Griswold (vujsa)
// Can be found at http://www.handyphp.com
// Do not remove this header!
function leading_zeros($value, $places){
  $leading = "";
    if(is_numeric($value)){
        for($x = 1; $x <= $places; $x++){
            $ceiling = pow(10, $x);
            if($value < $ceiling){
                $zeros = $places - $x;
                for($y = 1; $y <= $zeros; $y++){
                    $leading .= "0";
                }
            $x = $places + 1;
            }
        }
        $output = $leading . $value;
    }
    else{
        $output = $value;
    }
    return $output;
}

?>
