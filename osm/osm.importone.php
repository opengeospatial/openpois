<?php
/**
 * osm.importone.php
 * This script imports a single file in OpenStreetMap XML format. 
 * It's called by osm.import.php (but it can be used from the CLI too)
 * It extracts planet.osm <node>s and decides whether to process them based on it's contents.
 * Rules: 
 *   If the node doesn't have any <tag>s, it's not a POI, so ignore.
 *   If it only has transportation tags, ignore.
 *   Otherwise, pass the XML to a function to process it into a POI PHP object 
 *   and insert that into the database
 */
require_once('constants.php');
require_once('utils.php');
require_once('osm.utils.php');
require_once('class.poi.php');

$innode = FALSE;
$totalnodes = 0;
$matchednodes = 0;

function doNodeNode($node) {
  global $goodcategories, $totalnodes;

  $xml = simplexml_load_string($node);
  $tags = $xml->tag; // array of <tag> elements
  // echo sizeof($tags) . "\n";
  foreach ( $tags as $tag ) {
    // echo $tag['k'];
    $k = $tag['k'];
    if ( array_search($k, $goodcategories) == TRUE ) {
      $totalnodes++;
      $poi = goodNodeToPOI($xml, FALSE); // a function in osm.utils.php
      if ( !empty($poi) ) 
        // echo ($p->labels[0]->value . "\n");
        // echo ($p->AsXML() . "\n");
        $poi->updateDB();
      return;
    }
  }
  // echo($tags->asXML() . "\n\n");
  // echo ("\nend node\n");
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
      $nodeelement .= (" " . strtolower($key) . "=\"" . $value . "\"");
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
      $node .= (" " . strtolower($key) . "=\"" . htmlspecialchars($value,ENT_QUOTES,'UTF-8') . "\"");
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

try {
  // this should be this program name and an absolute file name
  $argv = $_SERVER['argv'];
  if ( empty($argv) || sizeof($argv) < 2 ) die ("no arguments passed. Please send OSM files to me!\n");

  $entry = $argv[1];
  logToDB($_SERVER['SCRIPT_NAME'] . " processing $entry", 'IMPORTINFO');
  
  $fp = fopen($entry, "r");
  if ( FALSE === $fp) {
    echo "couldn't open file!!!\n";
  }

  // loop through those temp files and import the data
  // set up xml parsing
  $xml_parser = xml_parser_create();
  xml_set_element_handler($xml_parser, "startTag", "endTag");
  xml_set_character_data_handler($xml_parser, "contents");       

  while ( $data = fread($fp, 4096) ) {
    xml_parse( $xml_parser, $data, feof($fp) ) or 
      die(sprintf('XML ERROR: %s at line %d', 
        xml_error_string(xml_get_error_code($xml_parser)), 
        xml_get_current_line_number($xml_parser)));
  }
  
  // clean up
  xml_parser_free($xml_parser);  
  fclose($fp);
} catch (Exception $e) {
  // logToDB("$d: Load of $fn failed: " . $e->getMessage());
  echo "$d: Load of $fn failed: " . $e->getMessage();
}

echo "total good nodes: $totalnodes\n";
echo "matched good nodes: $matchednodes\n";

?>
