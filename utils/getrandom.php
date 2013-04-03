<?php
/**
 * Get a random poi out of the database and dump it
 * October 21, 2011 
 * Raj Singh
 */
include ("XML/Serializer.php");
$options = array(
  XML_SERIALIZER_OPTION_INDENT        => '   ',
  XML_SERIALIZER_OPTION_RETURN_RESULT => true
  );
  
$query = $_SERVER['QUERY_STRING'];
$query = str_replace('&&', '----', $query);
$query = str_replace('"', '', $query);
$query = str_replace('+', '*****', $query);
parse_str($query, $query_array);
//Change the keys to upper case
$query="";
foreach ($query_array as $k => $v) {
	$kupper=strtoupper($k);
	$query .= "$kupper=$v"."&";
}
parse_str($query);


$m = new Mongo(); // connect to mongodb
$mdb = $m->poidb; // select a database
$coll = $mdb->poi; // select a collection

$targetidx = rand(0, $coll->count()-1);
$cursor = $coll->find();
$i = 0;

// send result to client
// echo $targetidx . "\n";

while ($i < $targetidx) {
  $cursor->getNext();
  $i++;
  // echo "skipped one...\n";
}

// get the poi, then use its ID to query again for it,
// but exclude the internal Mongo ID in the return this time
$r = $cursor->getNext();
$r = $coll->findOne( array('_id'=>$r['_id']), array('_id'=>0) );

if ( $FORMAT == "xml" || $FORMAT == "XML" ) {
  $serializer = new XML_Serializer($options);
  $result = $serializer->serialize($r);
  header("Content-Type: text/xml");
  echo $result;
} else {
  header("Content-Type: text/plain");
  echo json_encode($r);
}

?>
