<?php
include_once('class.geom.php');

Class Location extends POIBaseType {
  public $points = array();
  public $lines = array();
  public $polygons = array();
  public $address = NULL;
  public $undetermined = NULL;
  public $relationships = array();
  
  /**
   * Updates point poslist for the first point authored by Geonames
   */
  public function updateGNPoint($geom) {
    if ( sizeof($this->points) < 1 ) {
      $this->points[] = $geom;
      return true;
    }
    
    foreach ($this->points as $pt) {
      if ( $pt->hasAuthor() && $pt->author->isEquivalent($geom->author) ) {
        $pt->setPosList($geom->poslist);
        return true;

      } else { // this point has a different author
        $this->addPointGeom($geom);
        return true;
      }
    }
    return false;
  }

  /**
   * Checks if a point authored by Geonames has the same poslist
   */
  public function isGNEquivalent($loc) {
    $gpt = $loc->points[0];
    foreach ($this->points as $pt) {
      if ( $pt->hasAuthor() && $pt->author->isEquivalent($gpt->author) ) {
        if ( $pt->poslist == $gpt->poslist) 
          return true;
      }
    }
    return false;
  }

  /**
   * Get the number of sources present in the POI's location data.
   */
  public function getIds() {
    $sources = array();
    
    $sources[] = $this->id;
    
    foreach ($this->points as &$g) {
      $sources[] = $g->id;
    }
    foreach ($this->lines as &$g) {
      $sources[] = $g->id;
    }
    foreach ($this->polygons as &$g) {
      $sources[] = $g->id;
    }
    if ( !empty($this->address) ) {
      $sources[] = $this->address->id;
    }
    foreach ($this->relationships as &$r) {
      $sources[] = $r->id;
    }
    
    return array_filter($sources);
  }

  /**
  * Copy data from a <Location> SimpleXMLElement into Locations's class variables
  * @param xml SimpleXMLElement
  * @param typename POI type of the object. Used to override using the name of the XML element
  * @param locationobj Location PHP object to load data into. If null, then create a new one
   */
  static function loadXMLData($xml, $typename='LOCATION', $locationobj=NULL, $author=NULL) {
    if ( $locationobj == NULL ) $locationobj = new Location();
    $locationobj = POIBaseType::loadXMLData($xml, $typename, $locationobj);
    $locationobj->changed = true;

    // get all points
    foreach ( $xml->point as $pointxml ) {
      $g = Geom::loadXMLData($pointxml->Point, null, null, $author);
      if ( !empty($g) ) $locationobj->addPointGeom( $g );
    }

    // get all lines
    foreach ( $xml->line as $linexml ) {
      $g = Geom::loadXMLData($linexml->LineString, null, null, $author);
      if ( !empty($g) ) $locationobj->addLineGeom( $g );
    }

    // get all polygons
    foreach ( $xml->polygon as $polygonxml ) {
      $g = Geom::loadXMLData($polygonxml->SimplePolygon, null, null, $author);
      if ( !empty($g) ) $locationobj->addPolygonGeom( $g );
    }

    // get all the addresses
    foreach ( $xml->address as $addressxml ) {
      $a = POIBaseType::loadXMLData($addressxml, 'ADDRESS', null, $author);
      $locationobj->setAddress($a);
    }

    // get all the relationships
    foreach ( $xml->relationship as $relxml ) {
      $r = Relationship::loadXMLData($relxml, null, null, $author);
      $locationobj->addRelationship($r);
    }

    return $locationobj;
  }

  /**
   * Copy data from location database table to Location class variables
   * @param PDO array of a record from location database table $row
   * @param connection object to use for possible further querying $conn
   */
  static function loadDBData($row, $conn, $locationobj=NULL) {
    if ( $locationobj == NULL ) {
      $locationobj = new Location();
    }
    $locationobj = POIBaseType::loadDBData($row, $conn, $locationobj);

    // get all points
    $sql = "SELECT * FROM geo WHERE objname LIKE 'POINT' AND parentid = '" . $locationobj->myid . "' AND deleted IS NULL";
    //       echo "$sql\n";
    $c = $conn->query($sql);
    if ( $c ) {
      foreach ($c as $row) {
        $g = Geom::loadDBData($row, $conn);
        $locationobj->addPointGeom($g);
      }
    }

    // get all lines
    $sql = "SELECT * FROM geo WHERE objname LIKE 'LINESTRING' AND parentid = '" . $locationobj->myid . "' AND deleted IS NULL";
    //       echo "$sql\n";
    $c = $conn->query($sql);
    if ( $c ) {
      foreach ($c as $row) {
        $g = Geom::loadDBData($row, $conn);
        $locationobj->addLineGeom($g);
      }
    }

    // get all polygons
    $sql = "SELECT * FROM geo WHERE objname LIKE 'POLYGON' AND parentid = '" . $locationobj->myid . "' AND deleted IS NULL";
    $c = $conn->query($sql);
    if ( $c ) {
      foreach ($c as $row) {
        $g = Geom::loadDBData($row, $conn);
        $locationobj->addPolygonGeom($g);
      }
    }

    // get any address
    $sql = "SELECT * FROM POIBaseType WHERE objname LIKE 'ADDRESS' and parentid = '" . $locationobj->myid . "' AND deleted is NULL";
    $c = $conn->query($sql);
    if ( $c ) {
      foreach ($c as $row) {
        $a = POIBaseType::loadDBData($row, $conn);
        $locationobj->setAddress($a);
      }
    }

    // get all the relationships
    $sql = "SELECT * FROM relationship WHERE parentid = '" . $locationobj->myid . "' AND deleted IS NULL";
    $c = $conn->query($sql);
    if ( $c ) {
      foreach ($c as $row) {
        $rel = Relationship::loadDBData($row, $conn);
        $locationobj->addRelationship($rel);
      }
    }

    // get any undetermined stuff
    if ( !empty($row['undetermined']) )
      $locationobj->undetermined = $row['undetermined'];
      
    return $locationobj;
  }

  /**
   * $uuid: this element's uuid 
   * $conn: database connection
   */
  public static function deleteDB($uuid, $conn, $reallydelete=FALSE, $tablename='location') {
    try {
      // delete all relationships
      $sql = "SELECT myid FROM relationship WHERE parentid = '$uuid'";
      $c = $conn->query($sql);
      if ( $c ) {
        foreach ($c as $row) {
          Relationship::deleteDB($row['myid'], $conn, $reallydelete, 'relationship');
        }
      }
      
      // delete any address
      $sql = "SELECT myid FROM poibasetype WHERE parentid = '$uuid' AND objname like 'ADDRESS'";
      $c = $conn->query($sql);
      if ( $c ) {
        foreach ($c as $row) {
          POITermType::deleteDB($row['myid'], $conn, $reallydelete, 'poibasetype');
        }
      }
      
      // delete all geometries
      $sql = "SELECT myid FROM geo WHERE parentid = '$uuid'";
      $c = $conn->query($sql);
      if ( $c ) {
        foreach ($c as $row) {
          Geom::deleteDB($row['myid'], $conn, $reallydelete, 'geo');
        }
      }

      // delete this location object
      // $sql = "UPDATE $tablename SET updated = 'now', deleted = 'now' WHERE myid = '" . $uuid . "' AND deleted is NULL";
      parent::deleteDB($uuid, $conn, $reallydelete, $tablename);
      $conn->exec($sql);
    } catch (Exception $e) {
      throw $e;
    }
    return TRUE;
  }

  public function updateDB($parentuuid, $conn) {
    return $this->insertDB($parentuuid, $conn);
  }

  /**
   * $parentuuid: parent (the poi) element's uuid
   * $conn: database connection
   */
  public function insertDB($parentuuid, $conn) {
    if ( !empty($this->myid) && $this->changed == false) {
      if ( $this->author != NULL ) {
        $mid = $this->author->updateDB($this->myid, $conn);
        $this->author->setMyId($mid);
      }
      if ( $this->license != NULL ) {
        $mid = $this->license->updateDB($this->myid, $conn);
        $this->license->setMyId($mid);
      }
      return $this->myid;
    }

    if ( empty($this->myid) ) {
      $this->myid = gen_uuid();
    } else if ($this->changed) {
      $this->deleteDB($this->myid, $conn); // delete the old version in the DB
    }
    $this->parentid = $parentuuid;

    $sql = "insert into location";
    $sql .= " (myid,parentid,objname,id,value,href,type,authorid,licenseid,lang,base,created";
    $sql .= ",undetermined";
    $sql .= ") values (";
    $sql .= parent::getDBInsertValues($conn);
    if ( empty($this->undetermined) ) {
      $sql .= ", 'NULL'";
    } else {
      $sql .= ", '$this->undetermined'";
    }
    $sql .= ")";
    // echo "$sql\n";

    try {
      $conn->exec($sql);
    } catch (Exception $e) {
      throw $e;
    }
     
    foreach ($this->points as &$p) {
      $x = $p->updateDB($this->myid, $conn);
    }
    foreach ($this->lines as &$p) {
      $x = $p->updateDB($this->myid, $conn);
    }
    foreach ($this->polygons as &$p) {
      $x = $p->updateDB($this->myid, $conn);
    }
    foreach ($this->relationships as &$r) {
      $x = $r->updateDB($this->myid, $conn);
    }
    if ( !empty($this->address) ) {
      $x = $this->address->updateDB($this->myid, $conn);
    }
    return $this->myid;
  }

  function asRDF($timestamps=TRUE, $metadata=TRUE, $subject="", $whitesp="") {
    $x = parent::asRDF($timestamps, $metadata, $subject, $whitesp);
    // trim ending bracket off poibasetype expression
    $ends = strrpos($x, ']');
    if ( $ends !== FALSE ) {
      $x = substr($x, 0, $ends);
    }

    foreach ($this->points as &$pt) {
      $x .= $pt->asRDF($timestamps, $metadata, $subject, $whitesp.'  ');
    }
    foreach ($this->lines as &$line) {
      $x .= $line->asRDF($timestamps, $metadata, $subject, $whitesp.'  ');
    }
    foreach ($this->polygons as &$polygon) {
      $x .= $polygon->asRDF($timestamps, $metadata, $subject, $whitesp.'  ');
    }
    if ( $this->address ) 
      $x .= $this->address->asRDF($timestamps, $metadata, $subject, $whitesp.'  ');
    if ( $this->undetermined )
      $x .= "\n$whitesp $openpoitype:undetermined " . htmlspecialchars($this->undetermined) . " ; ";
    foreach ($this->relationships as &$rel) {
      $x .= $rel->asRDF($timestamps, $metadata, $subject, $whitesp.'  ');
    }

    $x .= $whitesp . "\n] . ";
    return $x;
  }
  
  function asXML($timestamps=TRUE, $metadata=TRUE) {
    $x = '';
    $x .= '<' . strtolower($this->typename);
    $atts = $this->getXMLAttributeSnippet($timestamps,$metadata);
    if ( strlen($atts) > 0 )
      $x .= ' ' . trim($atts);
    $x .= ">\n";

    foreach ($this->points as &$pt) {
      $x .= $pt->asXML($timestamps, $metadata);
    }
    foreach ($this->lines as &$line) {
      $x .= $line->asXML($timestamps, $metadata);
    }
    foreach ($this->polygons as &$polygon) {
      $x .= $polygon->asXML($timestamps, $metadata);
    }
    if ( $this->address ) 
      $x .= $this->address->asXML($timestamps, $metadata);
    if ( $this->undetermined )
      $x .= "<undetermined>" . htmlspecialchars($this->undetermined) . "</undetermined>\n";
    foreach ($this->relationships as &$rel) {
      $x .= $rel->asXML($timestamps, $metadata);
    }

    $x .= '</' . strtolower($this->typename) . '>' . "\n";
    return $x;
  }
  
  /**
   * Return the Y coordinate of the first point found
   * (assumes CRS of 4326)
   */
  function getY() {
    $pt = $this->getFirstPoint();
    if ( empty($pt) ) return FALSE;
    
    $poslist = $pt->getPosList();
    $cs = explode(' ',$poslist);
    return $cs[0];
  }
  
  /**
   * Return the X coordinate of the first point found
   * (assumes CRS of 4326)
   */
  function getX() {
    $pt = $this->getFirstPoint();
    if ( empty($pt) ) return FALSE;
    
    $poslist = $pt->getPosList();
    $cs = explode(' ',$poslist);
    return $cs[1];
  }
  
  function getFirstPoint() {
    if ( sizeof($this->points) > 0 ) {
      return $this->points[0];
    } else {
      return null;
    }
  }
  
  function getPoints() {
    return $this->points;
  }

  function addPointGeom($geom) {
    $this->points[] = $geom;
    $changed = true;
  }

  function getLines() {
    return $this->lines;
  }

  function addLineGeom($geom) {
    $this->lines[] = $geom;
    $changed = true;
  }

  function getPolygons() {
    return $this->polygons;
  }

  function addPolygonGeom($geom) {
    $this->polygons[] = $geom;
    $changed = true;
  }

  function addPoint($poslist, $srsname, $term='centroid') {
    $pt = new Geom('point', 'Point', $poslist, $term, $srsname);
    $pt->setParentId( $this->getMyId() );
    $this->points[] = $pt;
    $changed = true;
  }

  function removePoint($idx) {
    $this->removeObjectByIndex($this->points, $idx);
    $changed = true;
  }

  function addLine($poslist, $srsname, $term='loc') {
    $this->lines[] = new Geom('line', 'LineString', $poslist, $term, $srsname);
    $changed = true;
  }

  function removeLine($idx) {
    $this->removeObjectByIndex($this->lines, $idx);
    $changed = true;
  }

  function addPolygon($poslist, $srsname, $term='loc') {
    $this->polygons[] = new Geom('polygon', 'SimplePolygon', $poslist, $term, $srsname);
    $changed = true;
  }

  function removePolygon($idx) {
    $this->removeObjectByIndex($this->polygons, $idx);
    $changed = true;
  }

  function getAddress() {
    return $this->address;
  }

  /**
   * Set address as type VCARD if address contains with the text BEGIN:VCARD
   * Set address as free text otherwise.
   * Enter description here ...
   * @param POIBaseType representing the address $add
   */
  function setAddress($add) {
    if ( strpos($add->value, "BEGIN:VCARD") || strpos($add->value, "begin:vcard") )
      $add->type = 'text/directory';
    else
      $add->type = 'text/plain';
    $this->address = $add;
    $changed = true;
  }

  function removeAddress() {
    $a = $this->address;
    $this->address = NULL;
    return $a;
    $changed = true;
  }

  function getUndetermined() {
    return $this->undetermined;
  }

  function setUndetermined($value=NULL) {
    $this->undetermined = $value;
    $changed = true;
  }

  function removeUndetermined() {
    $this->undetermined = NULL;
    $changed = true;
  }

  function getRelationships() {
    return $this->relationships;
  }

  function addRelationship($relationship) {
    $this->relationships[] = $relationship;
    $changed = true;
  }

  function removeRelationship($idx) {
    $this->removeObjectByIndex($this->relationships, $idx);
    $changed = true;
  }

  function removeObjectByIndex(&$obj, $idx, $pgconn=null) {
    // delete from DB
    $prop = $obj[$idx];
    if ( isset($prop->myid) && $prop->myid != null ) {
      $prop->deleteDB($prop->myid, $pgconn);
    }
    
    if ( $idx < 0 || $idx >= sizeof($obj) )
      throw new Exception("Index out of bounds\n");
    unset($obj[$idx]);
    $obj = array_values($obj);
    $changed = true;
  }

  function __construct() {
    parent::__construct('LOCATION');
  }

}

?>