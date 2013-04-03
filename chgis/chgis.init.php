<?php
require_once('constants.php');
require_once('utils.php');
require_once('class.poi.php');

/**
 * chgis.init.php
 */
$chgisbaseurl = 'http://www.fas.harvard.edu/~chgis/';

$fn = $projbase . '/databases/CHGIS/v4_1820_cnty_pts_utf/v4_1820_cnty_pts_utf.txt';
echo "chgis.init.php processing $fn\n";

$fp = fopen($fn, "r");

if ( FALSE === $fp) die("couldn't open file, $fn!!!\n");

// this skips first line
while ( ($line=fgets($fp) ) != false ) { break; }

// while ( ($line=fgets($fp) ) != false && $i < 20) { // testing
while ( ($line=fgets($fp) ) != false ) {
  static $i = 0;
  $i++;
  $vs = explode(",", $line);
  
  // there will be 29 array members 
  // SYS_ID is the 1st, name is the 2nd, lon is 5th, lat is 6th, 
  // TYPE_PY is 8th, 
  // start year is 11th, end year is 13th
  $chgisid = $vs[1];
  $name = $vs[2];
  $lat = $vs[6];
  $lon = $vs[5];
  $type = $vs[8];
  $syr = $vs[11];
  $eyr = $vs[13];
  
  echo "processing line $i with id:$chgisid, name:$name [lat=$lat, lon=$lon], type: $type...\n";
  
  // build CHGIS objects
  //// poi
  $poiid = gen_uuid();
  $poi = new POI($poiid, NULL);

  //// author
  $poi->setAuthor( getCHGISMAuthor() );
  
  //// CHGIS link
  $l = new POITermType('LINK', 'canonical', NULL, $iana);
  $l->setBase( $chgisbaseurl);
  if ( !$l->setId($chgisid) ) {
    echo "ERROR SETTING ID!!!\n";
  }
  $l->setHref("http://chgis.hmdc.harvard.edu/xml/id/$chgisid");
  $l->setType('application/xml');
  $poi->updatePOITermTypeProperty($l);
    
  //// name 
  $l = new POITermType('LABEL', 'primary', $name, NULL);
  $l->setAuthor( getCHGISMAuthor() );
  $poi->updatePOITermTypeProperty($l);
  
  //// category
  $l = new POITermType('CATEGORY', 'TYPE_PY', $type, NULL);
  $l->setAuthor( getCHGISMAuthor() );
  $poi->updatePOITermTypeProperty($l);
  
  /// country
  $l = new POITermType('CATEGORY', 'cc', 'CN', NULL);
  $l->setAuthor( getCHGISMAuthor() );
  $poi->updatePOITermTypeProperty($l);
  
  //// location
  $loc = new Location();

  // points
  $poslist = $lat . ' ' . $lon;
  $geom = new Geom('point', 'Point', $poslist, 'centroid');
  $loc->addPointGeom($geom);
  $loc->setAuthor( getCHGISMAuthor() );
  $poi->location = $loc;
  
  // time
  if ( $syr == $eyr ) { // this is a time instant
    $l = new POITermType('TIME', 'instant', $syr, NULL);
    $l->setAuthor( getCHGISMAuthor() );
    $poi->updatePOITermTypeProperty($l);
  
  } else {
    $l = new POITermType('TIME', 'start', $syr, NULL);
    $l->setAuthor( getCHGISMAuthor() );
    $poi->updatePOITermTypeProperty($l);
    
    if ( $eyr != '1911') { // data stops at 1911 -- doesn't mean place ended its being then.
      $l = new POITermType('TIME', 'end', $eyr, NULL);
      $l->setAuthor( getCHGISMAuthor() );
      $poi->updatePOITermTypeProperty($l);
    }
  }
  
  // echo $poi->asXML();
  $poiuuid = $poi->updateDB();
  echo "inserted a new POI with UUID: $poiuuid\n";
  
  // on to the next POI
  continue;
} // end while

echo "chgis.init.php finished--processed $i lines of $fn\n";

?>
