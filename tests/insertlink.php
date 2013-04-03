<?php
include_once('../utils.php');
include_once('../class.poitermtype.php');

// Google Place
$link = new POITermType('LINK', 'related');
$link->setHref('http://maps.google.com/maps/place?cid=80072499531736922');
$link->setScheme('http://www.iana.org/assignments/link-relations/link-relations.xml');
$link->setType('text/html');
$link->setValue('GooglePlaces');
insertPOIObjectFromID($link, '269794192'); // insert link as child of Legal Seafoods in Kendall Sq. Cambridge

?>