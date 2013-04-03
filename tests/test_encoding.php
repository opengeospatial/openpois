<?php 
//$l = 'Bici&amp;Lindrico';
$l = '<o><node id="1363241794" version="1" timestamp="2011-07-16T06:28:39Z" uid="353312" user="Bici&amp;Lindrico" changeset="8736807" lat="45.8836845" lon="8.8124804"><tag k="name" v="Punto Panoramico"/><tag k="historic" v="wayside_cross"/></node></o>';

$l = 'Bici&Lindrico';
$l = htmlspecialchars($l,ENT_QUOTES,'UTF-8');
// $l = html_entity_decode($l);
//$l = htmlentities($l, ENT_XML1);
// $l = htmlspecialchars($l, ENT_XML1);

$l = "<node user=\"$l\"></node>";
$xml = simplexml_load_string($l);

// echo "$l\n";
print_r($xml);
?>