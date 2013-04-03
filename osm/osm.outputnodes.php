<?php
include_once('osm.utils.php');
include_once('class.poi.php');

$outdir = ".";
$file = "mass200klines.osm"; //"massachusetts.osm";
$outfile = $outdir . "/out.xml";
$handle = NULL;

$innode = FALSE;

/**
 * Take a <node> and decide whether to process it based on it's contents.
 * If it doesn't have any <tag>s, it's not a POI, so ignore.
 * If it only has transportation tags, ignore.
 * Otherwise, pass the XML to a function to process it into a POI PHP object.
 */
function doNodeNode($node) {
  global $handle;
  global $goodcategories;
  
  $ignore = TRUE;
  $xml = simplexml_load_string($node);
  $tags = $xml->tag; // array of <tag> elements

  foreach ( $tags as $tag ) {
    $k = $tag['k'];
    if ( array_search($k, $goodcategories) == TRUE ) {
      // echo goodNodeToPOI($xml)->asXML();
      if ( fwrite($handle, goodNodeToPOI($xml)->asXML()) === FALSE ) {
        echo "Couldn't write to file!\n";
        exit;
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

// set up a streaming XML parser
$xml_parser = xml_parser_create();
xml_set_element_handler($xml_parser, "startTag", "endTag");
xml_set_character_data_handler($xml_parser, "contents");

// open a new file for writing and empty it if it exists
if ( !$handle = fopen($outfile, 'w') ) {
  echo "Cannot open file ($outfile)\n";
  exit;
}
    
// open the OSM file for reading
clearstatcache(TRUE, $file);
$fp = fopen($file, "r");

while ( $data = fread($fp, 4096) ) {
  xml_parse( $xml_parser, $data, feof($fp) ) or 
    die(sprintf('XML ERROR: %s at line %d', 
      xml_error_string(xml_get_error_code($xml_parser)), 
      xml_get_current_line_number($xml_parser)));
}

xml_parser_free($xml_parser);

fclose($fp);
fclose($handle);

?>
