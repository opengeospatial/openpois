<?php
include_once('utils.php');
include_once('class.poi.php');

try {
  $conn = getDBConnection();

  // get poi record
  // should be only one row since any POI should have only one record without the deleted flag set
  $sql = "SELECT * FROM poibasetype WHERE objname LIKE 'POI' AND deleted IS NULL LIMIT 8";
  $c = $conn->query($sql);

  $pois = new DOMDocument('1.0', 'UTF-8');
  $pois->loadXML("<pois></pois>");
  $pois->preserveWhiteSpace = false;
  $pois->formatOutput = true;

  if ( $c ) {
    foreach ($c as $row) { 
      $p = POI::loadPOI( $row['id'] );
      $poi = new DOMDocument('1.0', 'UTF-8');      
      $poi->formatOutput = true;
      
      $poi->loadXML($p->asXML(FALSE));
      $node = $pois->importNode($poi->getElementsByTagName("poi")->item(0), TRUE);
      $pois->getElementsByTagName("pois")->item(0)->appendChild($node);
      
    }
  }
  echo $pois->saveXML();
} catch (Exception $e) {
  echo "POI QUERY FAIL: " . $e->getMessage() . "\n";
}

?>