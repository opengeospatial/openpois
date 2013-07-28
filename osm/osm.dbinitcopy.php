<?php
/**
 * osm.importone.php
 * Reads OSM XML files and writes text files in PostgreSQL COPY format
 */
require_once('constants.php');
require_once('utils.php');
require_once('osm.utils.php');
require_once('class.poi.php');

$dbconn = getDBConnection();
$innode = FALSE;
$nodeelement;
$node;
$firstchild;
$nodecontainsstuff;

// build directory of temporary files
$file = '/srv/openpoidb/databases/osm/RI/dfs';
// $file = '/var/lib/postgresql/osm/planet-latest.osm';
$d = dirname($file);
$dr = $d . '/tmp';
$outfiles = $d . '/dbfiles';

// files to write
$poibasetypefp = fopen($outfiles.'/poibasetype.in', "w");
$poitermtypefp = fopen($outfiles.'/poitermtype.in', "w");
$locationfp = fopen($outfiles.'/location.in', "w");
$geofp = fopen($outfiles.'/geo.in', "w");

// loop through each temporary file and process it
if ($handle = opendir($dr)) {
  while ( FALSE !== ($entry = readdir($handle)) ) {
    if ( strpos($entry, '.') === 0 ) continue; // skip dot files
    
	  $fp = fopen($dr . '/' . $entry, "r");
	  if ( FALSE === $fp) {
	    echo "couldn't open file $fp!!!\n";
	  } else {
			try {
				echo "Parsing $entry...\n";
				
			  // set up xml parsing
			  $xml_parser = xml_parser_create();
			  xml_set_element_handler($xml_parser, "startTag", "endTag");
			  xml_set_character_data_handler($xml_parser, "contents");

			  while ( $data = fread($fp, 4096) ) {
			    xml_parse( $xml_parser, $data, feof($fp) ) or 
			      die(sprintf("XML ERROR: %s at line %d\n", 
			        xml_error_string(xml_get_error_code($xml_parser)), 
			        xml_get_current_line_number($xml_parser)));
			  }

			  // clean up
			  xml_parser_free($xml_parser);  
			  fclose($fp);
			} catch (Exception $ex) {
			  echo "Load of $entry failed: " . $ex->getMessage();
			}
		}
    echo "Done importing $entry.\n";
  }

	// clean up output files
	fclose($poibasetypefp);
	fclose($poitermtypefp);
	fclose($locationfp);
	fclose($geofp);
}

function doNodeNode($node) {
  global $goodcategories;

  $xml = simplexml_load_string($node);

  $tags = $xml->tag; // array of <tag> elements
  // echo sizeof($tags) . "\n";
  foreach ( $tags as $tag ) {
    // echo $tag['k'];
    $k = $tag['k'];

    if ( array_search($k, $goodcategories) == TRUE ) {
      writePOI($xml);
      return;
    }

  }
}

/**
 * Do nothing. OSM doesn't have data in the CDATA
 */
function contents($parser, $data) {
}

function startTag($parser, $data, $attribs) {
  global $innode;
  global $nodeelement;
  global $node;
  global $firstchild;
  global $nodecontainsstuff;
  if ( $data == "NODE" ) {
    $nodeelement = "<" . strtolower($data);
		foreach ($attribs as $key=>$value) 
			$nodeelement .= (" " . strtolower($key) . "=\"" . htmlspecialchars($value) . "\"");
    $nodeelement .= ">\n";
    $innode = TRUE;
    $firstchild = TRUE;
  } else if ( $innode == TRUE ) {
    if ( $firstchild == TRUE ) {
      $node = $nodeelement;
      $nodeelement = "";
      $firstchild = FALSE;
      $nodecontainsstuff = TRUE;
    }
    $node .= "<" . strtolower($data);
    foreach ($attribs as $key=>$value) 
      $node .= (" " . strtolower($key) . "=\"" . htmlspecialchars($value) . "\"");
    // ENT_XML1 won't work until PHP 5.4
    // echo (" " . strtolower($key) . "=\"" . htmlspecialchars($value,ENT_XML1,'UTF-8') . "\"");
    $node .= "/>\n";
  }
}

function endTag($parser, $data){
  global $innode;
  global $nodeelement;
  global $node;
  global $nodecontainsstuff;
  if ( $data == "NODE" && $nodecontainsstuff) {
    $innode = FALSE;
    $nodecontainsstuff = FALSE;
    $node .= "</" . strtolower($data) . ">\n";
    doNodeNode($node);
  }
}


function writePOI($xml) {
	global $dbconn, $poibasetypefp, $poitermtypefp, $locationfp, $geofp;
  global $ogcbaseuri, $osmbaseurl, $osmdataurl, $osmweburl, $category_scheme, $badcategories, $descriptioncategories;
  global $authoridopenstreetmap, $licenseidopenstreetmap, $iana;

	// IF NO COORDS,  IT'S NOT IMPORTANT ENOUGH TO STORE!!
  if ( empty($xml->attributes()->lat) || empty($xml->attributes()->lon) ) return NULL;
  
  // build POI properties
	$n = '\N';
  
  //// poi
  $poiid = gen_uuid();
  $poitxt = "$poiid\t$n\tPOI\t$poiid\t$n\t$n\t$n\tNOW\tNOW\t$n\t$n\t$n\t$n\t$ogcbaseuri\t$n\n";
  fwrite($poibasetypefp, $poitxt);

	//// location
	$lon = (string)$xml->attributes()->lon;
	$lat = (string)$xml->attributes()->lat;
  $poslist = $lat . ' ' . $lon;
  $locid = gen_uuid();
  $geoid = gen_uuid();
  $geotxt = "$geoid\t$locid\tPOINT\t$n\t$n\t$n\t$n\tNOW\tNOW\t$n\t$authoridopenstreetmap\t$licenseidopenstreetmap\t$n\t$n\t$n\tcentroid\t$n\tPoint\t$n\t$poslist\tSRID=4326;POINT($lon $lat)\n";
  $loctxt = "$locid\t$poiid\tLOCATION\n";
  fwrite($locationfp, $loctxt);
  fwrite($geofp, $geotxt);
  
  // OSM link
  $osmid = (string)$xml->attributes()->id;
  $href = $osmdataurl . '/' . $osmid;
  $glid = gen_uuid();
  $gltxt = "$glid\t$poiid\tLINK\t$osmid\t$n\t$href\t$n\tNOW\tNOW\t$n\t$n\t$n\t$n\t$osmbaseurl\t$n\trelated\t$iana\n";
  fwrite($poitermtypefp, $gltxt);

  $tags = $xml->tag; // array of <tag> elements
  foreach ( $tags as $tag ) {
    $k = (string)$tag['k'];
    $v = (string)$tag['v'];
    
    if ( $k == 'name' ) {
		  if ( empty($v) ) return null; // IF THERE'S NO NAME, IT'S NOT IMPORTANT ENOUGH TO STORE!!

			//// label
		  $id = gen_uuid();
		  $ntxt = "$id\t$poiid\tLABEL\t$n\t$v\t$n\t$n\tNOW\tNOW\t$n\t$authoridopenstreetmap\t$n\t$n\t$n\t$n\tprimary\t$n\n";
		  fwrite($poitermtypefp, $ntxt);
    
    } else if ( !(array_search($k, $descriptioncategories) === FALSE) ) {
			//// description
		  $id = gen_uuid();
		  $ntxt = "$id\t$poiid\tDESCRIPTION\t$n\t$v\t$n\t$n\tNOW\tNOW\t$n\t$authoridopenstreetmap\t$licenseidopenstreetmap\t$n\t$n\t$n\n";
		  fwrite($poibasetypefp, $ntxt);

    } else { 
			//// categories
      if ( array_search($k, $badcategories) === FALSE && strpos($k, 'note') !== 0 ) {
				$v = pg_escape_literal($dbconn, $v);
				$s = $category_scheme . '#' . $k;
			  $id = gen_uuid();
			  $ntxt = "$id\t$poiid\tCATEGORY\t$n\t$v\t$n\t$n\tNOW\tNOW\t$n\t$authoridopenstreetmap\t$licenseidopenstreetmap\t$n\t$n\t$n\t$k\t$s\n";
			  fwrite($poitermtypefp, $ntxt);
      }
    }
  }
  
	return;
}

?>
