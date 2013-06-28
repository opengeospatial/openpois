<?php
require_once('constants.php');
require_once('class.poibasetype.php');

Class POITermType extends POIBaseType {
  public $term = NULL;
  public $scheme = NULL;

  /**
   * Copy data from a SimpleXMLElement into poibaseobj's POITermType class variables
   * @param xml SimpleXMLElement
   * @param typename POI type of the object. Used to override using the name of the XML element
   * @param poitermobj poitermtype PHP object to load data into. If null, then create a new one
   */
  static function loadXMLData($xml, $typename=NULL, $poitermobj=NULL, $author=NULL) {
    if ( empty($typename) ) $typename = strtoupper($xml->getName());
    if ( empty($poitermobj) ) $poitermobj = new POITermType($typename, $xml['term'] );
    $poitermobj = POIBaseType::loadXMLData($xml, $typename, $poitermobj, $author);
    $poitermobj->setTerm($xml['term']);
    if ( !empty($xml['scheme']) ) $poitermobj->setScheme($xml['scheme']);
    
    return $poitermobj;
  }

  /**
   * Copy data from poitermtype database table to POITermType class variables
   * @param PDO array of a record from poibasetype database table $row
   * @param connection object to use for possible further querying $conn
   */
  static function loadDBData($row, $conn, $poitermobj=NULL) {
    if ( $poitermobj == NULL ) {
      $poitermobj = new POITermType($row['objname'], $row['term'], $row['value'], $row['scheme']);
    }
    $poitermobj = POIBaseType::loadDBData($row, $conn, $poitermobj);
    return $poitermobj;
  }

  /**
   * $uuid: this element's uuid 
   * $conn: database connection
   */
  public static function deleteDB($uuid, $conn, $reallydelete=FALSE, $tablename='poitermtype') {
    parent::deleteDB($uuid, $conn, $reallydelete, $tablename);
  }

  public function updateDB($parentuuid, $conn) {
    return $this->insertDB($parentuuid, $conn);
  }

  /**
   * $parentuuid: parent (the poi) element's uuid 
   * $conn: database connection
   */
  public function insertDB($parentuuid, $conn) {
    // don't update the constant Geonames or OSM author records
    // just return their static id
    global $authoridgeonames, $authoridopenstreetmap, $authoridfactual, $authoridchgis;
    global $licenseidopenstreetmap, $authoridfutouring, $licenseidfutouring;
    if ( $this->myid == $authoridgeonames ) return $authoridgeonames;
    if ( $this->myid == $authoridfactual) return $authoridfactual;
    if ( $this->myid == $authoridchgis) return $authoridchgis;
    if ( $this->myid == $authoridopenstreetmap) return $authoridopenstreetmap;
    if ( $this->myid == $licenseidopenstreetmap) return $licenseidopenstreetmap;
    if ( $this->myid == $authoridfutouring) return $authoridfutouring;
    if ( $this->myid == $licenseidfutouring) return $licenseidfutouring;

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

    $sql = "insert into poitermtype";
    $sql .= " (myid,parentid,objname,id,value,href,type,authorid,licenseid,lang,base,created";
    $sql .= ",term,scheme";
    $sql .= ") values (";
    $sql .= parent::getDBInsertValues($conn);
    $sql .= ",'" . pg_escape_string($this->term) . "'";
    $sql .= "," . $this->ce($this->scheme);
    $sql .= ")";
    // echo "SQL: $sql\n\n";

    try {
      $conn->exec($sql);
    } catch (Exception $e) {
      throw $e;
    }
    return $this->myid;
  }
  
  public function asRDF($timestamps=TRUE, $metadata=TRUE, $subject="", $whitesp="") {
    global $openpoitype;
    $bx = parent::asRDF($timestamps, $metadata, $subject, $whitesp);
    // trim ending bracket off poibasetype expression
    $ends = strrpos($bx, ']');
    if ( $ends !== FALSE ) {
      $bx = substr($bx, 0, $ends);
    }

    // then add in poitermtype properties
    if ( $this->term != null ) { // term
      $bx .= "$whitesp dcterms:type \"" . $this->term . "\" ; ";
    }
    if ( $this->scheme != null ) { // scheme
      $bx .= "\n$whitesp dcterms:conformsTo <" . $this->scheme . "> ; ";
    }

    $bx .= $whitesp . "\n] . ";
    return $bx;
  }
  
  public function asXML($timestamps=TRUE, $metadata=TRUE) {
    $bx = '';
    $bx .= '<' . strtolower($this->typename);
    $atts = $this->getXMLAttributeSnippet($timestamps, $metadata);
    if ( strlen($atts) > 0 )
      $bx .= ' ' . trim($atts);

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
    $x .= parent::getXMLAttributeSnippet($timestamps, $metadata);
    if ( $this->term != NULL ) 
      $x .= " term=\"" . htmlspecialchars($this->term). "\"";
    if ( $this->scheme != NULL ) 
      $x .= " scheme=\"" . htmlspecialchars($this->scheme). "\"";
    return $x;    
  }

  /**
   * check if the input poitermtype is equivalent in all properties to this one
   */
  public function isEquivalent($b) {
    if ( $b->term != $this->term || $b->scheme != $this->scheme ) {
      return false;
    }
    return parent::isEquivalent($b);
  }
  
  public static function getRelatedLink() {
    return new POITermType('LINK', 'related', NULL, 'http://www.iana.org/assignments/link-relations/link-relations.xml');
  }

  public function getTerm() {
    return $this->term;
  }
  
  public function setTerm($v) {
    $this->term = $v;
    $this->changed = true;
  }
  
  public function getScheme() {
    return $this->scheme;
  }
  
  public function setScheme($surl) {
    // if (validateURL($surl) ) {
      $this->scheme = $surl;
      $this->changed = true;
      return TRUE;
    // } else {
    //   return FALSE;
    // }
  }

  function __construct($typename, $term, $value=NULL, $scheme=NULL) {
    parent::__construct($typename);
    $this->term = $term;
    $this->value = $value;
    if ( $scheme != NULL ) {
      if ( !$this->setScheme($scheme) ) {
        throw new Exception('scheme must be a proper URI');
      }
    }
  }

}

?>