<?php
require_once('constants.php');
require_once('geonames.constants.php');
require_once('utils.php');
require_once('class.poi.php');
global $iana;

/**
* geonames.modify.php
* Loads new or modified Geonames data to initialize the OpenPOI DB
 */

// this should be this program name and an absolute file name
$argv = $_SERVER['argv'];
if ( empty($argv) || sizeof($argv) < 2 ) 
  die ("geonames.modify.php: no arguments passed. Please send a Geonames dump or modification file to me!\n");
$fn = $argv[1];

logToDB("geonames.modify.php processing $fn", 'UPDATEINFO');
echo "geonames.modify.php processing $fn";

$fp = fopen($fn, "r");
if ( FALSE === $fp) {
  logToDB("geonames.modify.php couldn't open $fn", 'UPDATEINFO');
  die("couldn't open file, $fn!!!\n");
}

$i = 0;
while ( ($line=fgets($fp) ) != false ) {
  $i++;
  $vs = explode("\t", $line);
  
  // there will be 19 array members (but we only care about the first 9)
  // geonameid is the 1st, name is the 2nd, lat is 5th, lon is 6th, 
  // feature class is the 7th, feature code is 8th (both w scheme of http://www.geonames.org/export/codes.html)
  // and make feature class term fclass and feature code term fcode
  // country code is 9th and its term should be cc (what scheme??)
  $geonameid = $vs[0];
  $name = $vs[1];
  $lat = $vs[4];
  $lon = $vs[5];
  $fclass = $vs[6];
  $fcode = $vs[7];
  $cc = $vs[8];

  echo "processing line $i with id:$geonameid, name:$name [lat=$lat, lon=$lon]...\n";
  
  // build geonames objects
  //// author
  $ga = new POITermType('AUTHOR', 'publisher', 'geonames.org', NULL);
  
  //// link
  $gl = new POITermType('LINK', 'related', NULL, $iana);
  $gl->setBase($geonamesbaseurl);
  $gl->setId($geonameid);
  $gl->setHref($geonamesbaseurl . '/' . $geonameid);
  
  //// name 
  $gn = new POITermType('LABEL', 'primary', $name, NULL);
  $gn->setAuthor($ga);
  
  //// feature class
  $gfclass = new POITermType('CATEGORY', 'featureclass', $fclass, 'http://www.geonames.org/export/codes.html');
  $gfclass->setAuthor($ga);
  
  //// feature code
  $gfcode = new POITermType('CATEGORY', 'featurecode', $fcode, 'http://www.geonames.org/export/codes.html');
  $gfcode->setAuthor($ga);
  
  /// country
  $gfcc = new POITermType('CATEGORY', 'cc', $fcode, 'http://www.geonames.org/export/dump/countryInfo.txt');
  $gfcc->setAuthor($ga);
  
  //// location
  $loc = new Location();
  $poslist = $lat . ' ' . $lon;
  $geom = new Geom('POINT', 'Point', $poslist, 'centroid');
  $geom->setAuthor($ga);
  $loc->addPointGeom($geom);
  // echo "\nlocation:\n" . $loc->asXML() . "\n";
  
  // check for the geonames link in an existing POI
  $poiuuid = null;
  try {
    $pgconn = getDBConnection();
    $sql = "SELECT parentid from poitermtype ";
    $sql .= "WHERE objname LIKE 'LINK' AND id LIKE '$geonameid' AND base LIKE '$geonamesbaseurl' LIMIT 1";
    $c = $pgconn->query($sql);
    if ( $c ) {
      foreach ( $c as $row) {
        $poiuuid = $row['parentid'];
        echo "geonames.modify.php modifying POI with UUID: $poiuuid\n";
      }
    }
  } catch (Exception $e) {
    echo "SEARCH for geonames link FAIL: " . $e->getMessage() . "\n";
    return FALSE; // successful loading is false
  }
  
  $poi = null;
  if ( $poiuuid != null ) { // if it exists, fetch it
    $poi = POI::loadPOIUUID($poiuuid);
  } else { // if not, make a new one and add the geonames link
    $id = gen_uuid();
    $poi = new POI( $id, $ogcbaseuri);
    $poi->setMyId($id);
    $poi->updatePOIProperty($gl);
  }
  
  // now update the POI with all remaining GN info and write to DB
  $poi->updatePOIProperty($gn);
  $poi->updatePOIProperty($gfclass);
  $poi->updatePOIProperty($gfcode);
  $poi->updatePOIProperty($gfcc);
  if ( sizeof($poi->getLocation()->getPoints()) < 1) {
    $poi->setLocation($loc);
  } else if ( !$poi->getLocation()->isGNEquivalent($loc) ) {
    $poi->getLocation()->updateGNPoint($geom);
  }
  $poi->updateDB();
  
  // on to the next POI
  continue;
} // end while
logToDB("geonames.modify.php finished--processed $i lines of $fn", 'UPDATEINFO');

?>
