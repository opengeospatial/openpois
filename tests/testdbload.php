<?php
include ('class.poi.php');
$fn = "/Users/rajsingh/workspace/openpoidb/application/b.xml";
// $fn = "/Users/rajsingh/workspace/poi/rajpois/boston.xml";

function setPOIBaseValues($b, $xml) {
  // value
  if ( !empty($xml->value) ) {
    $b->value = (string)$xml->value;
  }
  // href
  if ( !empty($xml->href) ) {
    $b->href = (string)$xml->href;
  }
  // type
  if ( !empty($xml->type) ) {
    $b->type = (string)$xml->type;
  }
  // created
  if ( !empty($xml->created) ) {
    $b->created = (string)$xml->created;
  }
  // updated
  if ( !empty($xml->updated) ) {
    $b->updated = (string)$xml->updated;
  }
  // deleted
  if ( !empty($xml->deleted) ) {
    $b->deleted = (string)$xml->deleted;
  }
  // author
  if ( !empty($xml->author) ) {
    $a = setPOITermValues("AUTHOR", $xml->author);
    $b->author = $a;
  }
  // license
  if ( !empty($xml->license) ) {
      $term = "primary";
      $scheme = NULL;
    if ( !empty($xml->license->term) ) 
      $term = (string)$xml->license->term;
    if ( !empty($xml->license->scheme) ) 
      $scheme = (string)$xml->license->scheme;
    $l = new POITermType("LICENSE", $term, NULL, $scheme);
    $l = setPOIBaseValues($l, $xml->license);
    $b->license = $l;
  }
  // lang
  if ( !empty($xml->lang) ) {
    $b->lang = (string)$xml->lang;
  }
  // base
  if ( !empty($xml->base) ) {
    $b->base = (string)$xml->base;
  }
    
  return $b;
}

function setPOITermValues($termname, $xml, $term='primary') {
  $scheme = NULL;
  if ( !empty($xml->term) )
    $term = (string)$xml->term;
  if ( !empty($xml->scheme) )
    $scheme = (string)$xml->scheme;
  
  $d = new POITermType($termname, $term, NULL, $scheme);
  $d = setPOIBaseValues($d, $xml);
  return $d;
}

// load POI file into a generic PHP object
$xml = simplexml_load_file($fn);

// unmarshall it into a POI object
// id
$p = new POI( (string)$xml->id[0] );
$p = setPOIBaseValues($p, $xml);

//// POI-specific elements
// label
if ( count($xml->label) > 0 ) {
  foreach ($xml->label as $lbl) {
    $l = setPOITermValues("LABEL", $lbl);
    $p->addLabel($l);
  }
}
// description
if ( count($xml->description) > 0 ) {
  foreach ($xml->description as $desc) {
    $d = new POIBaseType("DESCRIPTION");
    $d = setPOIBaseValues($d, $desc);
    $p->addDescription($d);
  }
}
// category
if ( count($xml->category) > 0 ) {
  foreach ($xml->category as $cat) {
    $c = setPOITermValues("CATEGORY", $cat);
    $p->addCategory($c);
  }
}
// time
if ( count($xml->time) > 0 ) {
  foreach ($xml->time as $ti) {
    $t = setPOITermValues("TIME", $ti);
    $p->addTime($t);
  }
}
// link
if ( count($xml->link) > 0 ) {
  foreach ($xml->link as $link) {
    $l = setPOITermValues("LINK", $link);
    $p->addLink($l);
  }
}
// location!!!
if ( !empty($xml->location) ) {
  $loc = new Location('LOCATION');
  
  // points
  if ( count($xml->location->point) > 0 ) {
    foreach ($xml->location->point as $g) {
      $srsname = NULL;
      $term = "centroid";
      if ( !empty($g->term) ) 
        $term = (string)$g->term;
      if ( !empty($g->Point['srsName']) ) 
        $srsname = (string)$g->Point['srsName'];
      $poslist = (string)$g->Point->posList;
      $geom = new Geom("point", "Point", $poslist, $term, $srsname);
      $geom = setPOIBaseValues($geom, $g);
      $loc->addPointGeom($geom);
    }
  }
  // lines
  if ( count($xml->location->line) > 0) {
    foreach ($xml->location->line as $g) {
      $srsname = NULL;
      if ( !empty($g->LineString['srsName']) ) 
        $srsname = $g->LineString['srsName'];
      $poslist = $g->LineString->posList;
      $geom = new Geom("line", "LineString", $poslist, "route", $srsname);
      $geom = setPOIBaseValues($geom, $g);
      $loc->addLineGeom($geom);
    }
  }
  // polygons
  if ( count($xml->location->polygon) > 0 ) {
    foreach ($xml->location->polygon as $g) {
      $srsname = NULL;
      if ( !empty($g->Polygon['srsName']) ) 
        $srsname = $g->Polygon['srsName'];
      $poslist = $g->Polygon->posList;
      $geom = new Geom("polygon", "Polygon", $poslist, "geofence", $srsname);
      $geom = setPOIBaseValues($geom, $g);
      $loc->addPolygonGeom($geom);
    }
  }
  // address
  if ( !empty($xml->location->address) ) {
      $d = new POIBaseType("ADDRESS");
      $d = setPOIBaseValues($d, $xml->location->address);
      $loc->setAddress($d);
  }
  // undetermined
  if ( !empty($xml->location->undetermined) ) {
    $loc->setUndetermined( (string)$xml->location->undetermined );
  }
  // relationships
  if ( count($xml->location->relationship) > 0 ) {
    foreach ($xml->location->relationship as $rel) {
      $targetpoi = NULL;
      $term = "within";
      if ( !empty($rel->attributes()->targetPOI) ) 
        $targetpoi = (string)$rel->attributes()->targetPOI;
      if ( !empty($rel->attributes()->term) )
        $term = (string)$rel->attributes()->term;
      if ( !empty($rel->attributes()->targetPOI) ) {
        $r = new Relationship($targetpoi, $term);
        $r = setPOIBaseValues($r, $rel);
        $loc->addRelationship($r);
      } else {
        error_log('testdbload: Relationship with no targetPOI value');
      }
    }
  }
  // time
  if ( count($xml->location->time) > 0 ) {
    foreach ($xml->location->time as $ti) {
      $t = setPOITermValues("TIME", $ti);
      $loc->addTime($t);
    }
  }
}
$p->location = $loc;
// save the POI class to the database


// test output

$p->updateDB();

?>
