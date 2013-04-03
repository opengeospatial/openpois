<?php
$fn = "/Users/rajsingh/workspace/openpoidb/w3cpoi/rajpois/boston_simple.xml";
$xml = simplexml_load_file($fn);
$json = json_encode($xml);
var_dump( json_decode($json, TRUE) );
// var_dump($xml);
?>