<?php
/**
 * load POI file from DB into a POI object
 */
include ('../class.poi.php');

$json = false;
$xml = false;
$rdf = false; 
$html = false;
$pid = "8e240b51-5002-480d-afbe-30792752f607"; // baldpate hospital 
// $pid = "80139f06-ec8c-461c-8298-ec07d1495e69"; //lahey clinic

$argv = $_SERVER['argv'];
if ( empty($argv) || sizeof($argv) < 2 ) 
  die ("querybyid.php POI_ID [JSON|XML|RDF|HTML]\n");
  
if ( isset($argv[1]) ) {
  $pid = $argv[1];
}

if ( isset($argv[2]) ) {
  $fmt = strtolower($argv[2]);
  if ( $fmt == 'json') {
    $json = true;
  } else if ( $fmt == 'xml' ) {
    $xml = true;
  } else if ( $fmt == 'rdf' ) {
    $rdf = true;
  } else if ( $fmt == 'html' ) {
    $xml = html;
  }
} else {
  $xml = true;
}

$p = POI::loadPOI($pid);

if ( $p == FALSE ) {
  echo "Couldn't load POI.\n";
  exit(-1);
}

// test output
if ( $json ) {
  echo $p->asJSON();
} else if ( $xml ) { 
  // echo $p->asXML();
  $dom = new DOMDocument('1.0', 'UTF-8');
  $dom->preserveWhiteSpace = false;
  $dom->loadXML($p->asXML(false));
  $dom->formatOutput = true;
  echo $dom->saveXML();

} else if ( $rdf ) {
  echo $p->asRDF();
}

?>