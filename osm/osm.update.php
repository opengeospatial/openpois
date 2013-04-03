<?php
include_once('osm.utils.php');
include_once('class.poi.php');

$osmfilename = NULL;
$handle = NULL;
//$file = "sm.xml"; // "731.osc.xml"; // "mass200klines.osm"; //"massachusetts.osm";
$innode = FALSE;
$changetype = NULL;
// database variables
$conn = NULL;
$nodeelement = NULL;
$node = NULL;
$nodecontainsstuff = FALSE;

/**
 * Take a <node> and decide whether to process it based on it's contents.
 * If it doesn't have any <tag>s, it's not a POI, so ignore.
 * If it only has transportation tags, ignore.
 * Otherwise, pass the XML to a function to process it into a POI PHP object.
 */
function doNodeNode($node) {
  global $goodcategories;
  global $changetype;
  global $handle;

  $ignore = TRUE;
  $xml = simplexml_load_string($node);
  $tags = $xml->tag; // array of <tag> elements
  foreach ( $tags as $tag ) {
    $k = $tag['k'];
    if ( array_search($k, $goodcategories) == TRUE ) {
      
      if ( $changetype == 'DELETE' ) {
        $name = NULL;
        foreach ( $tags as $tag ) {
          $k = (string)$tag['k'];
          if ( $k == 'name' ) {
            $name = (string)$tag['v'];
          }
        }
        if ( empty($name) ) continue;
        
        $pois = POI::loadPOIsByLabel($name);
        foreach ( $pois as $poi ) {
          deleteOSMData($poi);
        }
        
      } elseif ( $changetype == 'MODIFY' || $changetype == 'CREATE') {
        $poi = goodNodeToPOI($xml);
        if ( !empty($p) ) 
          $p->updateDB();
      }
      
      return;
    }
  }
}

/**
 * Do nothing. OSM doesn't have data in the CDATA
 */
function contents($parser, $data){
}

function startTag($parser, $data, $attribs) {
  global $innode, $nodeelement, $node, $firstchild, $nodecontainsstuff, $changetype;
    
  if ( $data == "NODE" ) {
    $nodeelement = "<node";
    foreach ($attribs as $key=>$value) 
      $nodeelement .= (" " . strtolower($key) . "=\"" . $value . "\"");
    $nodeelement .= ">\n";
    $innode = TRUE;
    $firstchild = TRUE;
    
  // if <node> has any children, they will be tags and we want to consider this a POI
  } else if ( $innode == TRUE ) {
    if ( $firstchild == TRUE ) {
      $node = $nodeelement;
      $nodeelement = "";
      $firstchild = FALSE;
      $nodecontainsstuff = TRUE;
    }
    
    $node .= "<" . strtolower($data);
    foreach ($attribs as $key=>$value) 
      $node .= (" " . strtolower($key) . "=\"" . htmlspecialchars($value,ENT_QUOTES,'UTF-8') . "\"");
    // ENT_XML1 won't work until PHP 5.4
    // echo (" " . strtolower($key) . "=\"" . htmlspecialchars($value,ENT_XML1,'UTF-8') . "\"");
    $node .= "/>\n";
  }
  
  else {
    if ( $data == "MODIFY") {
      $changetype = "MODIFY";
      return;
    } elseif ( $data == "DELETE") {
      $changetype = "DELETE";
      return;
    } elseif ( $data == "CREATE") {
      $changetype = "CREATE";
      return;
    }    
  }
}

function endTag($parser, $data){
  global $innode, $nodeelement, $node, $nodecontainsstuff;

  if ( $data == "NODE" && $nodecontainsstuff) {
    $innode = FALSE;
    $nodecontainsstuff = FALSE;
    $node .= "</" . strtolower($data) . ">\n";
    doNodeNode($node);
  }
}

/**
 * returns the file name of the POIs
 */
function makeGoodChangeNodes($changenodefile) {
  global $osmbasedir, $handle, $conn;
  
  // $outfile = $osmbasedir . "out_change.xml";

  // set up a streaming XML parser
  $xml_parser = xml_parser_create();
  xml_set_element_handler($xml_parser, "startTag", "endTag");
  xml_set_character_data_handler($xml_parser, "contents");

  // open a new file for writing and empty it if it exists
  // if ( !$handle = fopen($outfile, 'w') ) {
  //   echo "Cannot open file ($outfile)\n";
  //   exit;
  // }

  // open the OSM file for reading
  clearstatcache(TRUE, $changenodefile);
  $fp = fopen($changenodefile, "r");
  
  // set up the database connection
  if ( $conn = !getDBConnection() ) {
    echo "Couldn't connect to database for processing changeset.\n";
    exit;
  }

  while ( $data = fread($fp, 4096) ) {
    xml_parse( $xml_parser, $data, feof($fp) ) or 
      die(sprintf('XML ERROR: %s at line %d', 
        xml_error_string(xml_get_error_code($xml_parser)), 
        xml_get_current_line_number($xml_parser)));
  }

  xml_parser_free($xml_parser);

  fclose($fp);
  // fclose($handle);
  // return $outfile;
}

/**
 * Get the OSM daily changeset, formatting the dates in 
 * UTC. It should be run after 2am UTC. 
 */
function getChangeSet() {
  global $osmbasedir, $osmfilename;
  
  $baseurl = 'http://planet.openstreetmap.org/daily/';

  // set the default timezone to use. Available since PHP 5.1
  // $tz = new DateTimeZone(DateTimeZone::UTC);
  date_default_timezone_set('UTC');
  $date = new DateTime();
  $osmfilename = '-' . $date->format('Ymd');

  $oneday = new DateInterval('P1D');
  $yesterday = $date->sub($oneday);
  $osmfilename = $yesterday->format('Ymd') . $osmfilename;
  $osmfilename .= '.osc.gz';
  $osmurl = $baseurl . $osmfilename;

  $downloadfile = $osmbasedir . $osmfilename;
  $fp = fopen($downloadfile, 'w');
  $ch = curl_init($osmurl);
  curl_setopt($ch, CURLOPT_FILE, $fp); // set the downloaded data to go into the file
  curl_setopt($ch, CURLOPT_HEADER, 0);
  curl_setopt($ch, CURLOPT_ENCODING, ''); // let curl un-gzip (DOESN'T WORK!!)
  $data = curl_exec($ch);

  // close cURL resource, and free up system resources
  curl_close($ch);
  fclose($fp);
  // inflate it
  exec('gunzip ' . $downloadfile);
  
  return $downloadfile;
}

$downloadfile = getChangeSet();
if ( empty($downloadfile) ) {
  echo "problem getting changeset file: $osmfilename\n";
  exit;
}

makeGoodChangeNodes($downloadfile);

?>
