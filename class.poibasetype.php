<?php
require_once('constants.php');
require_once('utils.php');

Class POIBaseType {
  public $typename = NULL;
  public $id = NULL;
  public $value = NULL;
  public $href = NULL;
  public $type = NULL; // type is a MIME type defined by RFC 2046
  public $created = NULL;
  public $updated = NULL;
  public $deleted = NULL;
  public $author = NULL;
  public $license = NULL;
  public $lang; // lang is defined by RFC3066
  public $base;
  public $myid; // database id
  public $parentid; // database id for this object's parent
  public $changed = FALSE;
  
  /**
   * Copy data from a SimpleXMLElement into poibaseobj's POIBaseType class variables
   * @param xml SimpleXMLElement
   * @param typename POI type of the object. Used to override using the name of the XML element
   * @param poibaseobj poibasetype PHP object to load data into. If null, then create a new one
   */
  static function loadXMLData($xml, $typename=NULL, &$poibaseobj=NULL) {
    $name = strtoupper($typename);
    if ( empty($name) ) $name = strtoupper( $xml->getName() );
    
    if ( $poibaseobj == NULL ) {
      $poibaseobj = new POIBaseType($name);
    } else {
      if ( $poibaseobj->typename == NULL ) $poibaseobj->typename = $name;
    }
    
    $poibaseobj->changed = true;
    $poibaseobj->id = $xml['id'];
    // if ( empty($poibaseobj->id) ) $poibaseobj->id = gen_uuid();
    $poibaseobj->myid = $xml['myid'];
    if ( empty($poibaseobj->myid) ) $poibaseobj->myid = gen_uuid();
    // set this upon return if necessary
    // $poibaseobj->parentid = $xml['parentid'];
		if ( !empty( $xml['value'] ) ) { // try <value> child element
			$poibaseobj->value = $xml['value'];
		} else if ( !empty( $xml->value) ) { // try attribute
	    $poibaseobj->value = $xml->value;
		} else if ( !empty( $xml[0] ) ) { // try CDATA of element
			$poibaseobj->value = htmlspecialchars( $xml[0] );
		}
    $poibaseobj->base = $xml['base'];
    $poibaseobj->href = $xml['href'];
    $poibaseobj->type = $xml['type'];
    $poibaseobj->lang = $xml['lang'];
    $poibaseobj->created = $xml['created'];
    $poibaseobj->updated = $xml['updated'];
    $poibaseobj->deleted = $xml['deleted'];

    if ( !empty($xml->author) ) {
      $auth = POITermType::loadXMLData($xml->author);
      $auth->parentid = $poibaseobj->myid;
      $poibaseobj->author = $auth;
    }

    if ( !empty($xml->license) ) {
      $lic = POITERMTYPE::loadXMLData($xml->license);
      $lic->parentid = $poibaseobj->myid;
      $poibaseobj->license = $lic;
    }
        
    return $poibaseobj;
  }
  
  /**
   * Copy data from poibasetype database table to POIBaseType class variables
   * @param row PDO array of a record from poibasetype database table $row
   * @param conn connection object to use to query DB for author or license info $conn
   * @param poibaseobj poibasetype PHP object to load data into. If null, then create a new one
   */
  static function loadDBData($row, $conn, $poibaseobj=NULL) {
    if ( $poibaseobj == NULL ) {
      $poibaseobj = new POIBaseType('http://dummy.example.com');
    }
    
    $poibaseobj->changed = false;
    $poibaseobj->myid = $row['myid'];
    $poibaseobj->parentid = $row['parentid'];
    $poibaseobj->id = $row['id'];
    $poibaseobj->typename = $row['objname'];
    $poibaseobj->base = $row['base'];
    $poibaseobj->value = $row['value'];
    $poibaseobj->href = $row['href'];
    $poibaseobj->type = $row['type'];
    $t = $row['created'];
    $poibaseobj->created = str_replace(" ", "T", $t);
    $t = $row['updated'];
    $poibaseobj->updated = str_replace(" ", "T", $t);
    $t = $row['deleted'];
    $poibaseobj->deleted = str_replace(" ", "T", $t);
    
    $a = $row['authorid'];
    if ( !empty($a) ) {
      $sql = "SELECT * FROM poitermtype WHERE myid = '" . $a . "' AND deleted IS NULL";
      $c = $conn->query($sql);
      if ( $c ) {
        foreach ($c as $newrow) { 
          // should be only one row bc any poibasetype should have only one author with no deleted flag set
          $t = POITermType::loadDBData($newrow, $conn);
          $poibaseobj->author = $t;
        }
      }
    }

    $l = $row['licenseid'];
    if ( !empty($l) ) {
      $sql = "SELECT * FROM poitermtype WHERE myid = '" . $l . "' AND deleted IS NULL";
      $c = $conn->query($sql);
      if ( $c ) {
        foreach ($c as $newrow) {
          // should be only one row since any poibasetype should have only one license without the deleted flag set
          $t = POITermType::loadDBData($newrow, $conn);
          $poibaseobj->license = $t;
        }
      }
    }
    
    $poibaseobj->lang = $row['lang'];
    
    return $poibaseobj;
  }
  
  /**
   * $uuid: this element's uuid 
   * $conn: database connection
   */
  public static function deleteDB($uuid, $conn, $reallydelete=FALSE, $tablename='poibasetype') {
    // don't delete the constant Geonames or OSM author records
    global $authoridgeonames, $authoridopenstreetmap, $authoridfactual, $authoridchgis;
    if ( $uuid == $authoridgeonames ) return TRUE;
    if ( $uuid == $authoridopenstreetmap) return TRUE;
    if ( $uuid == $authoridfactual) return TRUE;
    if ( $uuid == $authoridchgis) return TRUE;
    
    try {
      if ( $conn == null ) $conn = getDBConnection();
      
      // get author and license ids
      $sql = "SELECT authorid,licenseid FROM poibasetype WHERE myid = '" . $uuid . "' AND deleted is NULL";
      $c = $conn->query($sql);
      if ( $c ) {
        foreach ($c as $row) { 
          $authorid = $row['authorid'];
          $licenseid = $row['licenseid'];
          if ( !empty($authorid) ) {
            POITermType::deleteDB($authorid, $conn, $reallydelete);
          }
          if ( !empty($licenseid) ) {
            POITermType::deleteDB($licenseid, $conn, $reallydelete);
          }
        }
      }
      
      if ( $reallydelete ) {
        $sql = "DELETE FROM $tablename WHERE myid='$uuid' AND deleted IS NULL";
      } else {
        $sql = "UPDATE $tablename SET updated = 'now', deleted = 'now' WHERE myid = '" . $uuid . "' AND deleted is NULL";
      }
      $conn->exec($sql);
    } catch (Exception $e) {
      throw $e;
    }
    return TRUE;
  }

  /**
   * $parentuuid: parent (the poi) element's uuid 
   * $conn: database connection
   * @return this object's UUID
   */
  public function updateDB($parentuuid, $conn) {
    // echo "UPDATING a poibasetype of type $this->typename with ID: $this->id\n";
    // now insert a new version just like it was from scratch
    // but we need a flag so that the created date isn't changed
    return $this->insertDB($parentuuid, $conn);
  }

  /**
   * $parentuuid: parent (the poi) element's uuid 
   * $conn: database connection
   * @deprecated use updateDB
   */
  public function insertDB($parentuuid, $conn) {
    // don't update the constant Geonames or OSM author records
    // or the constant OSM license
    // just return their static id
    global $authoridgeonames, $authoridopenstreetmap, $authoridfactual, $authoridchgis, $authoriddbpedia;
    global $licenseidopenstreetmap, $authoridfutouring, $licenseidfutouring;
    if ( $this->myid == $authoridgeonames ) return $authoridgeonames;
    if ( $this->myid == $authoridfactual) return $authoridfactual;
    if ( $this->myid == $authoridchgis) return $authoridchgis;
    if ( $this->myid == $authoriddbpedia) return $authoriddbpedia;
    if ( $this->myid == $authoridopenstreetmap) return $authoridopenstreetmap;
    if ( $this->myid == $licenseidopenstreetmap) return $licenseidopenstreetmap;
    if ( $this->myid == $authoridfutouring) return $authoridfutouring;
    if ( $this->myid == $licenseidfutouring) return $licenseidfutouring;

    // echo "INSERTING a poibasetype of type $this->typename\n";
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

    $sql = "insert into poibasetype";
    $sql .= " (myid,parentid,objname,id,value,href,type,authorid,licenseid,lang,base,created";
    $sql .= ") values (";
    $sql .= $this->getDBInsertValues($conn);
    $sql .= ")";
    // echo "$sql\n";
    // if ( $this->typename == "POI") echo "inserting POI $this->id\n";
    
    try {
      $conn->exec($sql);
    } catch (Exception $e) {
      throw $e;
    }
    return $this->myid;
  }
  
  function getDBInsertValues($conn) {
    $x = "" . $this->ce($this->myid);
    $x .= "," . $this->ce($this->parentid);
    $x .= "," . $this->ce($this->typename);
    $x .= "," . $this->ce($this->id);
    $x .= "," . $this->ce( pg_escape_string(strip_tags($this->value)) );
    $x .= "," . $this->ce($this->href);
    $x .= "," . $this->ce($this->type);

    if ( $this->author != NULL ) {
      $authoruuid = $this->author->updateDB($this->myid, $conn);
      $x .= ",'$authoruuid'";
    } else {
      $x .= ",NULL";
    }
    
    if ( $this->license != NULL ) {
      $licenseuuid = $this->license->updateDB($this->myid, $conn);
      $x .= ", '$licenseuuid'";
    } else {
      $x .= ",NULL";
    }
    
    $x .= "," . $this->ce($this->lang);
    $x .= "," . $this->ce($this->base);

    if ( empty($this->created) ) {
      // $t = date('Ymd H:i:sO');
      // $t = str_replace("T", " ", $this->created);
      // $x .= "," . "'$t'";
      $x .= ",'now'";
    } else {
      // echo "USING this->created value: " . $this->created . "\n";
      $t = str_replace("T", " ", $this->created);
      $x .= ",'$t'";
    }
    
    // updated is always auto-generated by the DB in this insert
    // deleted is never appropriate on an INSERT

    return $x;
  }
  
  /**
   * Check if variable contents is empty
   */
  function ce($v) {
    if ( empty($v) ) {
      return 'NULL';
    } else {
      return "'" . addslashes($v) . "'";
    }
  }

  public function asRDF($timestamps=TRUE, $metadata=TRUE, $subject="", $whitesp="") {
    global $openpoitype;
    $lang = "";
    if ( $this->lang != null ) $lang = "@" . $this->lang;
    // object type
    // $bx = $whitesp . "\n";
    // if ( $this->typename == "AUTHOR" ) {
    //   $bx .= "dcterms:creator [";
    // } else if ( $this->typename == "LICENSE" ) {
    //   $bx .= "dcterms:RightsStatement [";
    // } else if ( $this->typename == "CATEGORY" ) {
    //   $bx .= "dcterms:subject [";
    // } else if ( $this->typename == "LINK" ) {
    //   $bx .= "dcterms:relation [";
    // } else if ( $this->typename == "LOCATION" ) {
    //   $bx .= "dcterms:spatial [";
    // } else {
    //   $bx .= $openpoitype . ':' . strtolower($this->typename) . " [";
    // }
    $bx = "\n$whitesp <".$subject."/" . strtolower($this->typename) . "#$this->myid> [ ";

    if ( $this->id != null ) { // id
      $bx .= "\n$whitesp rdfs:about " . $this->id . " ; ";
    }
    if ( $this->value != null ) { // value
      if ( $this->typename == "LABEL") {
        $bx .= "\n$whitesp dcterms:title \"" . $this->value . "\"$lang ; ";
      
      } else if ( $this->typename == "TIME") {
        $bx .= "\n$whitesp dcterms:valid \"" . $this->value . "\" ; ";

      } else if ( $this->typename == "DESCRIPTION") {
        $bx .= "\n$whitesp rdfs:comment \"" . $this->value . "\"$lang ; ";
      }
      $bx .= "\n$whitesp rdf:value \"" . $this->value . "\"$lang ; ";
    }
    if ( $this->href != null ) { // href
      $bx .= "\n$whitesp $openpoitype:href <" . $this->href . "> ; ";
    }
    if ( $this->type != null ) { // type
      $bx .= "\n$whitesp dcterms:format " . $this->type . " ; ";
    }
    if ( $timestamps ) { // created, updated, deleted
      if ( $this->created != null ) {
        $bx .= "\n$whitesp dcterms:created \"" . $this->created . "\"^^<http://www.w3.org/2001/XMLSchema#date> ; ";
      }
      if ( $this->updated != null ) {
        $bx .= "\n$whitesp dcterms:modified \"" . $this->updated . "\"^^<http://www.w3.org/2001/XMLSchema#date> ; ";
      }
      if ( $this->deleted != null ) {
        $bx .= "\n$whitesp $openpoitype:deleted \"" . $this->deleted . "\"^^<http://www.w3.org/2001/XMLSchema#date> ; ";
      }
    }
    if ( $this->author != null ) { // author
      // echo("author: \n");
      // var_dump($this->author);
      // $bx .= $author->asRDF($timestamps, $metadata, $whitesp.' ');
    }
    if ( $this->license != null ) { // license
      $bx .= $license->asRDF($timestamps, $metadata, $whitesp.' ');
    }
    
    $bx .= "\n" . $whitesp . '] . ';
    return $bx;
  }
  
  public function asXML($timestamps=TRUE, $metadata=TRUE) {
    $bx = '';
    $bx .= '<' . strtolower($this->typename);
    if ( strlen($this->getXMLAttributeSnippet($timestamps, $metadata)) > 0 )
      $bx .= ' ' . trim( $this->getXMLAttributeSnippet($timestamps, $metadata) );
    $xmlchildren = $this->getXMLElements($timestamps, $metadata);
    if ( $xmlchildren == NULL || strlen($xmlchildren)<1 ) {
      $bx .= " />\n";
    } else {
      $bx .= ">\n";
      $bx .= $xmlchildren;
      $bx .= '</' . strtolower($this->typename) . '>' . "\n";
    }
    return $bx;
  }
  
  protected function getXMLAttributeSnippet($timestamps=TRUE, $metadata=TRUE) {
    $x = '';
    if ( $this->id != NULL ) 
      $x .= " id=\"" . htmlspecialchars($this->id). "\"";
    if ( $this->href != NULL ) 
      $x .= " href=\"" . htmlspecialchars($this->href). "\"";
    if ( $this->type != NULL )
      $x .= " type=\"$this->type\"";
    if ( $timestamps ) {
      if ( $this->created != NULL )
        $x .= " created=\"$this->created\"";
      if ( $this->updated != NULL )
        $x .= " updated=\"$this->updated\"";
      if ( $this->deleted != NULL )
        $x .= " deleted=\"$this->deleted\"";
    }
    if ( $this->lang != NULL )
      $x .= " lang=\"$this->lang\"";
    if ( $this->base != NULL )
      $x .= " base=\"" . htmlspecialchars($this->base). "\"";
    return $x;    
  }
  
  protected function getXMLElements($timestamps=TRUE, $metadata=TRUE) {
    $x = '';
    if ( $this->value != NULL )
      $x .= '<value>' . htmlspecialchars($this->value) . '</value>' . "\n";
    if ( $this->author != NULL )
      $x .= $this->author->asXML($timestamps, $metadata);
    if ( $this->license != NULL )
      $x .= $this->license->asXML($timestamps, $metadata);
    return $x;
  }
  
  /**
   * check if the input poibasetype is equivalent in all properties to this one
   */
  public function isEquivalent($b) {
    if ( $b->typename == $this->typename && $b->id == $this->id && 
          $b->value == $this->value && 
          $b->href == $this->href && $b->type == $this->type && 
          $b->lang == $this->lang && $b->base == $this->base ) {
      if ( ($b->author == null && $this->author != null) || ($b->author != null && $this->author == null) ) {
        return false;
      }
      if ( ($b->license == null && $this->license != null) || ($b->license != null && $this->license == null) ) {
        return false;
      }
      ////// @TODO: FIX THIS 
      if ( ($b->author != null && $this->author != null) ) {
        if ( !($this->author->isEquivalent($b->author)) ) return false;
      }
      ////// @TODO: FIX THIS 
      // if ( ($b->license != null && $this->license != null) ) {
      //   if ( !($this->license->isEquivalent($b->license)) ) return false;
      // }
    } else {
      return false;
    }
    return true;
  }
  
  public function getMyId() {
    return $this->myid;
  }

  public function setMyId($v) {
    $this->myid = $v;
    $this->changed = true;
  }
  
  public function getParentId() {
    return $this->parentid;
  }

  public function setParentId($v) {
    $this->parentid = $v;
    $this->changed = true;
  }

  public function getTypename() {
    return $this->typename;
  }

  public function setTypename($v) {
    $this->typename = $v;
    $this->changed = true;
  }

  public function getValue() {
    return $this->value;
  }

  public function setValue($v) {
    $this->value = $v;
    $this->changed = true;
  }

  public function getType() {
    return $this->type;
  }

  public function setType($v) {
    $this->type = $v;
    $this->changed = true;
  }

  public function getCreated() {
    return $this->created;
  }

  public function setCreated($v) {
    $this->created = $v;
    $this->changed = true;
  }

  public function getUpdated() {
    return $this->updated;
  }

  public function setUpdated($v) {
    $this->updated = $v;
    $this->changed = true;
  }

  public function getDeleted() {
    return $this->deleted;
  }

  public function setDeleted($v) {
    $this->deleted = $v;
    $this->changed = true;
  }

  public function getAuthor() {
    return $this->author;
  }

  public function setAuthor($v) {
    $this->author = $v;
    $this->changed = true;
  }
  
  public function hasAuthor() {
    if ( $this->author != null ) return true;
    return false;
  }

  public function getLicense() {
    return $this->license;
  }

  public function setLicense($v) {
    $this->license = $v;
    $this->changed = true;
  }

  public function getLang() {
    return $this->lang;
  }

  public function setLang($v) {
    $this->lang = $v;
    $this->changed = true;
  }

  public function getId() {
    return $this->id;
  }

  // NOT ANYMORE!! id must be a url, or must be a url when appended to the base value
  public function setId($id) {
    // if ( validateURL($id) ) {
      $this->id = $id;
      $this->changed = true;
      return TRUE;
    // } else {
    //   $u = $this->base . '/' . $id;
    //   if ( validateURL($u) ) {
    //     $this->id = $id;
    //     return TRUE;
    //   }
    //   return FALSE;
    // }
  }

  public function getHref() {
    return $this->href;
  }

  public function setHref($hrefurl) {
    if ( validateURL($hrefurl) ) {
      $this->href = $hrefurl;
      $this->changed = true;
      return TRUE;
    } else {
      return FALSE;
    }
  }

  public function getBase() {
    return $this->base;
  }

  public function setBase($baseurl) {
    if (validateURL($baseurl)) {
      $this->base = $baseurl;
      $this->changed = true;
      return TRUE;
    } else {
      return FALSE;
    }
  }
  
  public function getDBStylePOIID() {
    $i = $this->id;
    if ( !empty($this->base) ) {
      $j = $this->base;
      $j = rtrim($i, "/");
      $i = $j . "/" . $i;
    }
    return $i;
  }

  function __construct($typename) {
    if ( $typename == NULL ) {
      throw new Exception('object must have a name ...');
    }
    $this->typename = strtoupper( $typename );
  }
}

?>