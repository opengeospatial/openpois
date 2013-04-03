<?php
require_once('constants.php');
require_once('geonames.constants.php');
require_once('utils.php');

/**
 * geonames.initcopy.php
 * Reads a geonames dump file and writes text files in PostgreSQL COPY format
 */
// $gndb = '/srv/openpoidb/databases/geonames/';
$gndb = '/var/lib/postgresql/geonames/';
$fn = $gndb . 'allCountries.txt'; //'US/RI.txt'; 
$copydir = $gndb . "/tmp/"; // local
$authorid = $authoridgeonames;

$n = '\N';
logToDB("geonames.initcopy.php processing " . addslashes($fn), 'UPDATEINFO');

$fp = fopen($fn, "r");
$poibasetypefp = fopen($copydir.'poibasetype.in', "w");
$poitermtypefp = fopen($copydir.'poitermtype.in', "w");
$locationfp = fopen($copydir.'location.in', "w");
$geofp = fopen($copydir.'geo.in', "w");

if ( FALSE === $fp) {
  logToDB("geonames.initcopy.php couldn't open " . addslashes($fn), 'UPDATEINFO');
  die("couldn't open file, $fn!!!\n");
}

$i = 0;
// while ( ($line=fgets($fp) ) != false && $i < 20) { // testing
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
  //// poi
  $poiid = gen_uuid();
  $poitxt = "$poiid\t$n\tPOI\t$poiid\t$n\t$n\t$n\tNOW\tNOW\t$n\t$n\t$n\t$n\t$ogcbaseuri\t$n\n";
  fwrite($poibasetypefp, $poitxt);

  //// author
  // $ga = new POITermType('AUTHOR', 'publisher', 'geonames.org', NULL);
  // need to add myid and parent id to beginning of this
  // $authortxtend = "AUTHOR\thttp://geonames.org\t$n\t$n\t$n\tNOW\tNOW\t$n\t$n\t$n\t$n\t$n\t$n\tpublisher\t$n\n";
  
  //// geonames link
  $geonameshref = $geonamesbaseurl . '/' . $geonameid;
  $glid = gen_uuid();
  $gltxt = "$glid\t$poiid\tLINK\t$geonameid\t$n\t$geonameshref\t$n\tNOW\tNOW\t$n\t$n\t$n\t$n\t$geonamesbaseurl\t$n\trelated\t$iana\n";
  fwrite($poitermtypefp, $gltxt);
    
  //// name 
  $id = gen_uuid();
  $gntxt = "$id\t$poiid\tLABEL\t$n\t$name\t$n\t$n\tNOW\tNOW\t$n\t$authorid\t$n\t$n\t$n\t$n\tprimary\t$n\n";
  fwrite($poitermtypefp, $gntxt);
  
  //// feature class
  $id = gen_uuid();
  $gfclasstxt = "$id\t$poiid\tCATEGORY\t$n\t$fclass\t$n\t$n\tNOW\tNOW\t$n\t$authorid\t$n\t$n\t$n\t$n\tfeatureclass\t$category_scheme\n";
  fwrite($poitermtypefp, $gfclasstxt);
  
  //// feature code
  $id = gen_uuid();
  $gfcodetxt = "$id\t$poiid\tCATEGORY\t$n\t$fcode\t$n\t$n\tNOW\tNOW\t$n\t$authorid\t$n\t$n\t$n\t$n\tfeaturecode\t$category_scheme\n";
  fwrite($poitermtypefp, $gfcodetxt);
  
  /// country
  $id = gen_uuid();
  $gfcctxt = "$id\t$poiid\tCATEGORY\t$n\t$cc\t$n\t$n\tNOW\tNOW\t$n\t$authorid\t$n\t$n\t$n\t$n\tcc\t$country_scheme\n";
  fwrite($poitermtypefp, $gfcctxt);
  
  //// location
  $poslist = $lat . ' ' . $lon;
  $id = gen_uuid();
  $geoid = gen_uuid();
  $geotxt = "$geoid\t$id\tPOINT\t$n\t$n\t$n\t$n\tNOW\tNOW\t$n\t$authorid\t$n\t$n\t$n\t$n\tcentroid\t$n\tPoint\t$n\t$poslist\tSRID=4326;POINT($lon $lat)\n";
  $loctxt = "$id\t$poiid\tLOCATION\n";
  fwrite($geofp, $geotxt);
  fwrite($locationfp, $loctxt);
  
  // $poi->updateDB();
  
  // on to the next POI
  continue;
} // end while

fclose($poibasetypefp);
fclose($poitermtypefp);
fclose($locationfp);
fclose($geofp);

// system("psql -U poidbadmin -d openpoidb -f /srv/openpoidb/application/geonames/geonames.initcopy.sql");
logToDB("geonames.initcopy.php finished--processed $i lines of " . addslashes($fn), 'UPDATEINFO');

?>
