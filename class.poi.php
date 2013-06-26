<?php
include_once('constants.php');
include_once('class.relationship.php');
include_once('class.location.php');

Class POI extends POIBaseType {
  var $labels = array();
  var $descriptions = array();
  var $categories = array();
  var $times = array();
  var $links = array();
  var $metadata = NULL;
  var $location = NULL;
  
  /**
   * Copy data from one POI into another
   * @param $poi a POI object
   */
  function mergePOI($poi) {
    foreach ( $poi->labels as $label) 
      $this->updatePOITermTypeProperty($label);

    foreach ( $poi->descriptions as $description) 
      $this->updatePOIBaseTypeProperty($description);

    foreach ( $poi->categories as $category) 
      $this->updatePOITermTypeProperty($category);

    foreach ( $poi->times as $time) 
      $this->updatePOITermTypeProperty($time);

    foreach ( $poi->links as $link) 
      $this->updatePOITermTypeProperty($link);

    foreach ( $poi->location as $location) {
      if ( !empty($location->point) ) {
        foreach ( $location->point as $point ) 
          $this->updatePOITermTypeProperty($point);
      }
      if ( !empty($location->line) ) {
        foreach ( $location->line as $line ) 
          $this->updatePOITermTypeProperty($line);
      }
      if ( !empty($location->polygon) ) {
        foreach ( $location->polygon as $polygon ) 
          $this->updatePOITermTypeProperty($polygon);
      }
        
      // $this->updatePOITermTypeProperty($location);
      $this->updatePOIBaseTypeProperty($poi);
    }
    
    return $this;
  }

  /**
   * Copy data from a SimpleXMLElement into a POI PHP object
   * @param xml SimpleXMLElement
   */
  static function loadXMLData($xml, $typename='POI', &$poi=NULL) {
    $poi = new POI( gen_uuid(), NULL );
    $poi = POIBaseType::loadXMLData($xml, 'POI', $poi);

    foreach ( $xml->label as $label) {
      $poi->labels[] = POITermType::loadXMLData($label);
      $poi->changed = true;
    }

    foreach ( $xml->description as $description) {
      $d = POIBaseType::loadXMLData($description);
      $poi->descriptions[] = $d;
      $poi->changed = true;
    }

    foreach ( $xml->category as $category) {
      $c = POITermType::loadXMLData($category);
      $poi->categories[] = $c;
      $poi->changed = true;
    }

    foreach ( $xml->time as $time) {
      $t = POITermType::loadXMLData($time);
      $poi->times[] = $t;
      $poi->changed = true;
    }

    foreach ( $xml->link as $link) {
      $l = POITermType::loadXMLData($link);
      $poi->times[] = $l;
      $poi->changed = true;
    }

    foreach ( $xml->location as $location) {
      $poi->location = Location::loadXMLData($location, NULL, $poi->location);
      $poi->changed = true;
    }
    
    return $poi;
  }

  /**
   * Check if the same description is already in this POI. 
   * The same means the same id, base and author.
   * If there's a match, but all fields are equal, do nothing and return false. 
   * If there's a match but not all fields are equal, update it (and write to database) and return true.
   * If no match, add a new one (and write to the database) and return true. 
   */
  public function updatePOIBaseTypeProperty($prop, $writedb=FALSE) {
    $t = $prop->getTypeName();
    if ( $t == 'DESCRIPTION') $props = $this->descriptions;
    else if ( $t == 'POI') $props = array($this);
    else return false;

    foreach ($props as $c) {
      if ( $prop->isEquivalent($c) ) {
        return false;
      }
    } // end foreach
    
    if ( $t == 'DESCRIPTION' ) {
      $this->addDescription($prop);
    
    // if we have a POI object here, we can't add another, so let's create a link to its online resource
    } else if ( $t == 'POI' ) {
      if ( !empty($this->href) ) {
        $lk = new POITermType('LINK', 'related');
        $lk->setHref( $prop->getHref() );
        $lk->setId( $prop->getId() );
        $this->addLink($lk);
      }
    }

    if ( $writedb ) {
      $this->updateDB();
    }
    return true;
  }
  
  /**
   * Check if the same POITermType property is already in this POI. 
   * Supported types: label, category, time, link
   * The same means the same base, author, term and scheme.
   * If there's a match -- all fields are equal -- do nothing and return false. 
   * If no match, add a new one and (optionally) write to the database and return true. 
   */
  public function updatePOITermTypeProperty($ptt, $writedb=FALSE) {
		if ( empty($ptt) ) return false;
		
    $t = $ptt->getTypeName();
    $props = null;
    if ( $t == 'LABEL') $props = $this->labels;
    else if ( $t == 'CATEGORY') $props = $this->categories;
    else if ( $t == 'TIME') $props = $this->times;
    else if ( $t == 'LINK') $props = $this->links;
    else return NULL; // we don't know how to process it

    foreach ($props as $c) { // loop through each label, category, time or link
      if ( $ptt->isEquivalent($c) ) { // if completely equal, don't bother going any further
        // echo "returning isEquivalent doing nothing with term " . $ptt->getTerm() . "...\n";
        return false; // false because nothing was updated
      }
    } // end foreach
    
    // echo "IM HERE with ptt term " . $ptt->getTerm() . "\n";
    
    if ( $t == 'LABEL') $this->addLabel($ptt);
    else if ( $t == 'CATEGORY') $this->addCategory($ptt);
    else if ( $t == 'TIME') $this->addTime($ptt);
    else if ( $t == 'LINK') $this->addLink($ptt);

    if ( $writedb ) {
      $this->updateDB();
    }
    return true;
  }
    
  /**
   * Check if the same input source is already in this POI. 
   * The same means the same id, href, type and base.
   * If so, ignore and return false. If not, add it and write to the database and return true. 
    * @deprecated use updatePOITermTypeProperty
   */
  public function updateSource($link) {
    $found = false;
    foreach ($this->links as &$l) {
      if ( $link->id == $l->id && $link->href == $l->href && 
            $link->type == $l->type && $link->base == $l->base) {
        $found = true;
        echo "found a matching link.\n";
        return false;
      }
    }
    
    if ( !$found ) {
      $this->addLink($link);
      $link->insertDB($this->getMyId(), getDBConnection());
      return true;
    }
  }
  
  /**
   * Get the number of sources present in the POI data.
   */
  public function getIds() {
    $sources = array();
    
    $sources[] = $this->id;
    
    foreach ($this->labels as &$label) {
      $sources[] = $label->id;
    }
    foreach ($this->descriptions as &$d) {
      $sources[] = $d->id;
    }
    foreach ($this->categories as &$c) {
      $sources[] = $c->id;
    }
    foreach ($this->times as &$t) {
      $sources[] = $t->id;
    }
    foreach ($this->links as &$l) {
      $sources[] = $l->id;
    }
      
    $sources = array_merge($sources, $this->location->getIds());
    
    return array_filter($sources);
  }
  
  /**
   * Get the POI ID using its UUID as a lookup
   */
  public static function getPOIID($poiuuid) {
    try {
      $conn = getDBConnection();
      // should be only one row since any POI should have only one record without the deleted flag set
      $sql = "SELECT id FROM poibasetype WHERE ";
      $sql .= "objname LIKE 'POI' AND myid LIKE '" . $poiuuid . "' AND deleted IS NULL LIMIT 1";

      $c = $conn->query($sql);
      if ( $c ) {
        foreach ($c as $row) { 
          $id = $row['id'];
          $conn = NULL;
          return $id;
        }
      } else {
          return FALSE;
      }
    } catch (Exception $e) {
      echo "POI GET POIID FAIL: " . $e->getMessage() . "\n";
      return FALSE; // successful loading is false
    }
  }

  /**
   * Get the POI's UUID using its ID as a lookup
   */
  public static function getPOIUUID($poiid) {
    try {
      $conn = getDBConnection();
      // get poi record
      // should be only one row since any POI should have only one record without the deleted flag set
      $sql = "SELECT myid FROM poibasetype WHERE ";
      $sql .= "objname LIKE 'POI' AND id LIKE '" . $poiid . "' AND deleted IS NULL LIMIT 1";

      $c = $conn->query($sql);
      if ( $c ) {
        foreach ($c as $row) { 
          $uuid = $row['myid'];
          $conn = NULL;
          return $uuid;
        }
      } else {
          return FALSE;
      }
    } catch (Exception $e) {
      echo "POI GET POIUUID FAIL: " . $e->getMessage() . "\n";
      return FALSE; // successful loading is false
    }
  }

  /**
   * strict = false will take a LOOOONG time to complete...
   */
  public static function loadPOIsByLabel($label, $strict=true) {
    $label = addslashes($label);
    $pois = array();
    
    try {
      $sql = "SELECT parentid from poitermtype where objname like 'LABEL'";
      if ( $strict ) {
        $sql .= " AND value LIKE '$label'";
      } else {
        $sql .= " AND value LIKE '$label%'";
      }

      $conn = getDBConnection();
      $c = $conn->query($sql);      
      if ( $c ) {
        foreach($c as $row) {
          $pois[] = POI::LoadPOIUUID($row['parentid']);
        }
      }
      
      return $pois;
    } catch (Exception $e) {
      echo "loadPOIByLabel() FAIL: " . $e->getMessage() . "\n";
    }
    return FALSE; // successful loading is false
  }

  /**
   * Get a POI from the database using the POI's ID
   */
  public static function loadPOI($poiid) {
    $uuid = POI::getPOIUUID($poiid);
    if ( !empty($uuid) ) {
      return POI::loadPOIUUID($uuid);
    } else {
      echo "POI LOAD FAIL in loadPOI(): Couldn't find POI with ID $poiid\n";
      return FALSE; // successful loading is false
    }
    
    return TRUE; // successful loading    
  }
  
  /**
   * Get a POI from the database using the POI's internal 'myid' (UUID)
   */
  public static function loadPOIUUID($poiuuid) {
    $conn = NULL;
    $poi = NULL;
    
    try {
      $conn = getDBConnection();
      // get poi record
      // should be only one row since any POI should have only one record without the deleted flag set
      $sql = "SELECT * FROM poibasetype WHERE ";
      $sql .= "myid='$poiuuid' AND objname LIKE 'POI' AND deleted IS NULL LIMIT 1";
      $c = $conn->query($sql);
      if ( $c ) {
        foreach ($c as $row) { 
          $poi = new POI($row['id'], $row['base']);
          $poi = parent::loadDBData($row, $conn, $poi);
        }
      }
      
      // get all the labels for the poi
      $sql = "SELECT * FROM poitermtype WHERE ";
      $sql .= "parentid='$poiuuid' AND objname LIKE 'LABEL' AND deleted IS NULL";
      $c = $conn->query($sql);
      if ( $c ) {
        foreach ($c as $row) {
          $o = POITermType::loadDBData($row, $conn);
          $poi->addLabel($o);
        }
      }
      
      // get all the descriptions for the poi
      $sql = "SELECT * FROM poibasetype WHERE ";
      $sql .= "parentid='$poiuuid' AND objname LIKE 'DESCRIPTION' AND deleted IS NULL";
      $c = $conn->query($sql);
      if ( $c ) {
        foreach ($c as $row) {
          $o = POIBaseType::loadDBData($row, $conn);
          $poi->addDescription($o);
        }
      }

      // get all the categories for the poi
      $sql = "SELECT * FROM poitermtype WHERE ";
      $sql .= "parentid='$poiuuid' AND objname LIKE 'CATEGORY' AND deleted IS NULL";
      $c = $conn->query($sql);
      if ( $c ) {
        foreach ($c as $row) {
          $o = POITermType::loadDBData($row, $conn);
          $poi->addCategory($o);
        }
      }
      
      // get all the times for the poi
      $sql = "SELECT * FROM poitermtype WHERE ";
      $sql .= "parentid='$poiuuid' AND objname LIKE 'TIME' AND deleted IS NULL";
      $c = $conn->query($sql);
      if ( $c ) {
        foreach ($c as $row) {
          $o = POITermType::loadDBData($row, $conn);
          $poi->addTime($o);
        }
      }
      
      // get all the links for the poi
      $sql = "SELECT * FROM poitermtype WHERE ";
      $sql .= "parentid='$poiuuid' AND objname LIKE 'LINK' AND deleted IS NULL";
      $c = $conn->query($sql);
      if ( $c ) {
        foreach ($c as $row) {
          $o = POITermType::loadDBData($row, $conn);
          $poi->addLink($o);
        }
      }
      
      // metadata unimplemented

      // get the location for the poi
      $sql = "SELECT * FROM location WHERE ";
      $sql .= "parentid='$poiuuid' AND deleted IS NULL LIMIT 1";
      $c = $conn->query($sql);
      if ( $c ) {
        foreach ($c as $row) {
          $loc = Location::loadDBData($row, $conn);
          $poi->location = $loc;
        }
      }
      
    } catch (Exception $e) {
      echo "POI QUERY FAIL: " . $e->getMessage() . "\n";
      $conn = NULL;
      return FALSE; // successful loading is false
    }

    $conn = null;
    return $poi; // successful loading
  }
  
  /**
   * Take the UUID for a POI and delete it all all its components.
   * 
   * @param poiid either the UUID ($myid) or the ID ($id)
   * @param selectbypoiid if FALSE (the default) then poiid is a myid. If TRUE, poiid is an id
   * @param reallydelete If FALSE (the default), simply modify the database records 
    * by setting deleted and updated timestamps set to 'now'. If TRUE, 
    * then actually delete the records from the database. Records 
    * should only be really deleted as an administrative cleanup task where 
    * there's an error in the data. All public interfaces to the system should 
    * only set the deleted and updated timestamps.
   */
  public static function deleteDB($poiid, $selectbypoiid=FALSE, $reallydelete=FALSE, $shutupstrict=TRUE) {    
    try {
      $conn = getDBConnection();
      $myid = $poiid;
      // $sbiclause = '';
      if ( $selectbypoiid ) {
        $myid = POI::getPOIUUID($poiid);
        // $sbiclause = " AND id LIKE '$poiid'";
      }
      if ( $myid == NULL ) return FALSE;

      $conn->beginTransaction();
      
      // get the location for the poi
      $sql = "SELECT myid FROM location WHERE ";
      $sql .= "parentid = '$myid'"; // . $sbiclause;
      $sql .= " AND objname LIKE 'LOCATION'";
      $c = $conn->query($sql);
      if ( $c ) {
        foreach ($c as $row) {
          Location::deleteDB($row['myid'], $conn, $reallydelete);
        }
      }      

      // delete all poitermtypes
      $sql = "SELECT myid FROM poitermtype WHERE ";
      $sql .= "parentid = '$myid'";
      $c = $conn->query($sql);
      if ( $c ) {
        foreach ($c as $row) {
          POITermType::deleteDB($row['myid'], $conn, $reallydelete);
        }
      }
      
      // delete all poibasetypes
      $sql = "SELECT myid FROM poibasetype WHERE ";
      $sql .= "parentid = '$myid'";
      $c = $conn->query($sql);
      if ( $c ) {
        foreach ($c as $row) {
          POIBaseType::deleteDB($row['myid'], $conn, $reallydelete);
        }
      }

      // metadata unimplemented

			// delete the POI itself
      $sql = "SELECT myid FROM poibasetype WHERE ";
      $sql .= "myid = '$myid'";
      $c = $conn->query($sql);
      if ( $c ) {
        foreach ($c as $row) {
          POIBaseType::deleteDB($row['myid'], $conn, $reallydelete);
        }
      }

      $conn->commit();      
    } catch (Exception $e) {
      echo "POI DELETE failed: " . $e->getMessage();
      $conn = NULL;
    }
    
    $conn = NULL;
    return TRUE;
  }
  
  /**
   * Writes the POI to the database, but doesn't write any parts that already have 
   * a value for their myid variable if their changed flag is set to false.
   * 
   * Write poi to poibasetype table with objname of poi and set myid.
   * Write labels to poitermtype table with objname of label and parentid of poi's myid and set myid.
   * Write descriptions to poibasetype table with objname of descriptions and poiid of POI id and parentid of poi's myid and set myid.
   * Write categories to poitermtype table with objname of categories and poiid of POI id and parentid of poi's myid and set myid.
   * Write times to poitermtype table with objname of time and poiid of POI id and parentid of poi's myid and set myid.
   * Write links to poitermtype table with objname of links and poiid of POI id and parentid of poi's myid and set myid.
   * Write metadata to metadata table with objname of metadata and poiid of POI id and parentid of poi's myid and set myid.
   */
  public function updateDB($parentuuid=NULL, $conn=NULL) {
    $poiuuid = null;
    try {
      if ( empty($conn) ) $conn = getDBConnection();      
      $conn->beginTransaction();
            
      $poiuuid = parent::updateDB(NULL, $conn);

      foreach ($this->labels as &$label) {
        $x = $label->updateDB($poiuuid, $conn);
      }
      foreach ($this->descriptions as &$d) {
        $x = $d->updateDB($poiuuid, $conn);
      }
      foreach ($this->categories as &$c) {
        $x = $c->updateDB($poiuuid, $conn);
      }
      foreach ($this->times as &$t) {
        $x = $t->updateDB($poiuuid, $conn);
      }
      foreach ($this->links as &$l) {
        $x = $l->updateDB($poiuuid, $conn);
      }
      if ( $this->metadata != NULL ) {
        $x = $this->metadata->updateDB($poiuuid, $conn);
      }
      if ( $this->location != NULL ) {
        $this->location->updateDB($poiuuid, $conn);
      }
      
      $conn->commit();
    } catch (Exception $e) {
      trigger_error("POI update failed: " . $e->getMessage());
    }
    
    $conn = null;
    return $poiuuid;
  }

  /**
   * @see POIBaseType::asJSON()
   */
  function asJSON($JSON_OPTIONS=0) {
    return json_encode($this, $JSON_OPTIONS);
  }

  /**
   * @see POIBaseType::asHTML()
   */
  function asHTML($timestamps=FALSE, $metadata=FALSE) {
    $x = '\n<div itemscope itemtype="http://schema.org/POI">';
    $x .= '\n\t<div class="id">permalink: <span itemprop="id">' . $this->getId() . '</span></div>';
    
    return $x;
  }
  
  /**
   * Returns Turtle RDF as defined by http://www.w3.org/TR/turtle/
   * @see POIBaseType::asRDF()
   */
  function asRDF($timestamps=FALSE, $metadata=FALSE, $subject="", $whitesp='') {
    global $ogcbaseuri;
    // $x = "\n<div itemscope itemtype=\"http://schema.org/POI\">";
    $x = "\n@base <$ogcbaseuri> .";
    $x .= "\n@prefix dcterms: <http://purl.org/dc/terms/> .";
    $x .= "\n@prefix rdfs: <http://www.w3.org/2000/01/rdf-schema#> .";
    $x .= "\n@prefix rdf: <http://www.w3.org/1999/02/22-rdf-syntax-ns#> .";
    $x .= "\n@prefix foaf: <http://xmlns.com/foaf/0.1/> .";
    $x .= "\n@prefix dbpedia-owl: <http://dbpedia.org/ontology/> .\n";
    
    // id
    $x .= "\n</" . $this->id . "> openpoi:id </" . $this->id . "> ; ";
    $x .= "\n  rdf:about </" . $this->id . "> ; ";
    $x .= "\n  rdfs:isDefinedBy </" . $this->id . ".rdf> . ";
    
    foreach ($this->labels as &$label) {
      $x .= "\n</" . $this->id . "> openpoi:label </$this->id/label#$label->myid> .";
      $x .= $label->asRDF($timestamps, $metadata, "/$this->id");
    }
    foreach ($this->descriptions as &$d) {
      $x .= "\n</" . $this->id . "> openpoi:description </$this->id/description#$d->myid> .";
      $x .= $d->asRDF($timestamps, $metadata, "/$this->id");
    }
    foreach ($this->categories as &$c) {
      $x .= "\n</" . $this->id . "> dcterms:subject </$this->id/category#$c->myid> .";
      $x .= $c->asRDF($timestamps, $metadata, "/$this->id");
    }
    foreach ($this->times as &$t) {
      $x .= "\n</" . $this->id . "> openpoi:time </$this->id/time#$t->myid> .";
      $x .= $t->asRDF($timestamps, $metadata, "/$this->id");
    }
    foreach ($this->links as &$l) {
      $x .= "\n</" . $this->id . "> openpoi:link </$this->id/link#$l->myid> .";
      $x .= $l->asRDF($timestamps, $metadata, "/$this->id");
    }
    if ( $this->metadata ) {
      $x .= "\n $openpoitype:metadata " . htmlspecialchars($this->metadata) . " ; ";
    }
    $x .= "\n</" . $this->id . "> dcterms:spatial </$this->id/location#$l->myid> .";
    $x .= $this->location->asRDF($timestamps, $metadata, "/$this->id");
    
    return $x . "\n";
  }
  
  /**
   * @see POIBaseType::asXML()
   */
  function asXML($timestamps=FALSE, $metadata=TRUE) {
    $x = '';
    $x .= '<' . strtolower($this->typename);
    
    $atts = $this->getXMLAttributeSnippet($timestamps, $metadata);
    if ( strlen($atts) > 0 )
      $x .= ' ' . trim($atts);
    $x .= ">\n";
    $x .= $this->getXMLElements($timestamps, $metadata);
    foreach ($this->labels as &$label) {
      $x .= $label->asXML($timestamps, $metadata);
    }
    foreach ($this->descriptions as &$d) {
      $x .= $d->asXML($timestamps, $metadata);
    }
    foreach ($this->categories as &$c) {
      $x .= $c->asXML($timestamps, $metadata);
    }
    foreach ($this->times as &$t) {
      $x .= $t->asXML($timestamps, $metadata);
    }
    foreach ($this->links as &$l) {
      $x .= $l->asXML($timestamps, $metadata);
    }
    if ( $this->metadata ) {
      $x .= "\n<metadata>" . htmlspecialchars($this->metadata) . "</metadata>";
    }
    $x .= $this->location->asXML($timestamps, $metadata);
    $x .= '</' . strtolower($this->typename) . '>' . "\n";
    
    return $x;
  }
  
  function getLocation() {
    return $this->location;
  }
  
  function setLocation($loc) {
    $this->location = $loc;
    $changed = true;
  }

  function addMetadata($value=NULL) {
    $this->metadata = $value;
    $changed = true;
  }

  function removeMetadata() {
    $this->metadata = NULL;
    $changed = true;
  }
  
  function addLink($link) {
    $link->setParentId( $this->getMyId() );
    $this->links[] = $link;
    $changed = true;
  }

  function removeLink($idx) {
    $this->removeObjectByIndex($this->links, $idx);
    $changed = true;
  }
  
  function addTime($time) {
    // TODO check if time value is valid
    $time->setParentId( $this->getMyId() );
    $this->times[] = $time;
    $changed = true;
  }

  function removeTime($idx) {
    $this->removeObjectByIndex($this->times, $idx);
    $changed = true;
  }

  function addCategory($category) {
    $category->setParentId( $this->getMyId() );
    $this->categories[] = $category;
    $changed = true;
  }

  function removeCategory($idx) {
    $this->removeObjectByIndex($this->categories, $idx);
    $changed = true;
  }

  function addDescription($description) {
    $description->setParentId( $this->getMyId() );
    $this->descriptions[] = $description;
    $changed = true;
  }

  function removeDescription($idx) {
    $this->removeObjectByIndex($this->descriptions, $idx);
    $changed = true;
  }
  
  function getFirstLabelName() {
    if ( empty($this->labels[0]) ) return false;
    $l = $this->labels[0];
    return $l->getValue();
  }

  function addLabel($label) {
    $label->setParentId( $this->getMyId() );
    $this->labels[] = $label;
    $changed = true;
  }

  function removeLabel($idx) {
    $this->removeObjectByIndex($this->labels, $idx);
    $changed = true;
  }

  function removeObjectByIndex($obj, $idx, $pgconn=null) {
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
  
  function addCenterPoint($poslist) {
    $this->location->addPoint($poslist, NULL, 'center');
    $changed = true;
  }

  function __construct($id, $base) {
    parent::__construct('POI');
    if ( !empty($base) ) $this->setBase($base);
    $this->setId($id);
    $this->setMyId($id);
    $this->location = new Location('LOCATION');
  }

} // end class POI

?>