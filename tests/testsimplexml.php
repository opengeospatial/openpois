<?php
$fn = "http://www.rajsingh.org/poiwg/c_error.xml";
$fn = "/Users/rajsingh/workspace/openpoidb/w3cpoi/rajpois/faneuilhall.xml";
$xml = simplexml_load_file($fn);

// if ( !empty($xml->link) ) {
//   echo "number of links: " . sizeof($xml->link) . "\n";
//   foreach ($xml->link as $link) {
//     echo "link href: " . $link['href'] . "\n";
//   }
// }
// 
// if ( !empty($xml->relationship) ) {
//   echo "number of relationships: " . sizeof($xml->relationship) . "\n";
//   foreach ($xml->relationship as $rel) {
//     echo "relationship term: " . $rel['term'] . "\n";
//   }
// }

$i = count($xml->link);
if ( $i > 0 ) {
  echo "number of links: $i\n";
  foreach ($xml->link as $link) {
    echo "link href: " . $link['href'] . "\tvalue: " . $link->value . "\n";
  }
}

$i = count($xml->relationship);
if ( $i > 0 ) {
  echo "number of relationships: $i\n";
  foreach ($xml->relationship as $rel) {
    echo "relationship term: " . $rel['term'] . "\n";
  }
}
?>