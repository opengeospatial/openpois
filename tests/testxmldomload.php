<?php
include ('class.poi.php');
//$fn = "/Users/rajsingh/workspace/poi/rajpois/boston.xml";
$fn = "/Users/rajsingh/workspace/openpoidb/application/b.xml";
$fn = "/Users/rajsingh/workspace/openpoidb/w3cpoi/rajpois/faneuilhall.xml";

function setPOIBaseValues($b, $xml) {
  // id
  if ( !empty($xml->attributes()->id) ) {
    $b->id = (string)$xml->attributes()->id;
  }
  // value
  if ( !empty($xml->value) ) {
    $b->value = (string)$xml->value;
  }
  // href
  if ( !empty($xml->attributes()->href) ) {
    $b->href = (string)$xml->attributes()->href;
  }
  // type
  if ( !empty($xml->attributes()->type) ) {
    $b->type = (string)$xml->attributes()->type;
  }
  // created
  if ( !empty($xml->attributes()->created) ) {
    $b->created = (string)$xml->attributes()->created;
  }
  // updated
  if ( !empty($xml->attributes()->updated) ) {
    $b->updated = (string)$xml->attributes()->updated;
  }
  // deleted
  if ( !empty($xml->attributes()->deleted) ) {
    $b->deleted = (string)$xml->attributes()->deleted;
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
    if ( !empty($xml->license->attributes()->term) ) 
      $term = (string)$xml->license->attributes()->term;
    if ( !empty($xml->license->attributes()->scheme) ) 
      $scheme = (string)$xml->license->attributes()->scheme;
    $l = new POITermType("LICENSE", $term, NULL, $scheme);
    $l = setPOIBaseValues($l, $xml->license);
    $b->license = $l;
  }
  // lang
  if ( !empty($xml->attributes()->lang) ) {
    $b->lang = (string)$xml->lang;
  }
  // base
  if ( !empty($xml->attributes()->base) ) {
    $b->base = (string)$xml->base;
  }
    
  return $b;
}

function setPOITermValues($termname, $xml, $term='primary') {
  $scheme = NULL;
  if ( !empty($xml->attributes()->term) )
    $term = (string)$xml->attributes()->term;
  if ( !empty($xml->attributes()->scheme) )
    $scheme = (string)$xml->attributes()->scheme;
  
  $d = new POITermType($termname, $term, NULL, $scheme);
  $d = setPOIBaseValues($d, $xml);
  return $d;
}

// load POI file into a generic PHP object
$xml = simplexml_load_file($fn);

// unmarshall it into a POI object
// id
$p = new POI( (string)$xml->attributes()->id );
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
  $loc = new Location();
  
  // points
  if ( count($xml->location->point) > 0 ) {
    foreach ($xml->location->point as $g) {
      $srsname = NULL;
      $term = "centroid";
      if ( !empty($g['term']) ) 
        $term = (string)$g['term'];
      if ( !empty($g->Point['srsName']) ) 
        $srsname = (string)$g->Point['srsName'];
      $poslist = (string)$g->Point->posList;
      $geom = new Geom("point", "Point", $poslist, $term, $srsname);
      $geom = setPOIBaseValues($geom, $g);
      $loc->addPointGeom($geom);
    }
  }
  // lines
  if ( count($xml->location->line) > 0 ) {
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
    echo "processing relationship...\n";
    foreach ($xml->location->relationship as $rel) {
      $targetpoi = NULL;
      $term = "within";
      if ( !empty($rel['targetPOI']) ) 
        $targetpoi = (string)$rel['targetPOI'];
      if ( !empty($rel['term']) )
        $term = (string)$rel['term'];
      if ( !empty($rel['targetPOI']) ) {
        $r = new Relationship($targetpoi, $term);
        $r = setPOIBaseValues($r, $rel);
        $loc->addRelationship($r);
      } else {
        error_log('testxmlload: Relationship with no targetPOI value');
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

// test output
$dom = new DOMDocument('1.0', 'UTF-8');
$dom->preserveWhiteSpace = false;
$dom->loadXML($p->asXML());
$dom->formatOutput = true;
echo $dom->saveXML();
// print_r($p->location);

?>