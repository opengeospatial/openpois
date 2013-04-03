<?php
$fn = "/Users/rajsingh/workspace/openpoidb/w3cpoi/rajpois/boston_simple.json";
$json = file_get_contents($fn);

var_dump( json_decode($json, TRUE) );
?>