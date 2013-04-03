<?php
require_once('constants.php');
include_once('utils.php');
include_once('class.poitermtype.php');

Class Relationship extends POITermType{
  public $targetPOI;
    
  /**
   * Copy data from a SimpleXMLElement into poiobj's Relationship class variables
   * @param xml SimpleXMLElement
   * @param typename POI type of the object. Used to override using the name of the XML element
   * @param poiobj relationship PHP object to load data into. If null, then create a new one
   */
  static function loadXMLData($xml, $typename=NULL, &$poiobj=NULL) {
    if ( empty($poiobj) ) $poiobj = new Relationship($xml['targetPOI'], $xml['term'] );
    $poiobj = POITermType::loadXMLData($xml, $typename, $poiobj);
    $poiobj->changed = true;
    $poiobj->targetPOI = $xml['targetPOI'];
    
    return $poiobj;
  }

  /**
   * Copy data from relationship database table to Relationship class variables
   * @param PDO array of a record from relationship database table $row
   * @param connection object to use for possible further querying $conn
   */
  static function loadDBData($row, $conn, $relobj=NULL) {
    if ( $relobj == NULL ) {
      $relobj = new Relationship($row['targetpoi'], $row['term']);
    }
    $relobj = POITermType::loadDBData($row, $conn, $relobj);
    return $relobj;
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

    $sql = "insert into relationship";
    $sql .= " (myid,parentid,objname,id,value,href,type,authorid,licenseid,lang,base,created";
    $sql .= ",term,scheme,targetpoi";
    $sql .= ") values (";
    $sql .= parent::getDBInsertValues($conn);
    $sql .= ",'" . addslashes($this->term) . "'";
    $sql .= ",'" . $this->ce($this->scheme) . "'";
    $sql .= ",'" . addslashes($this->targetPOI) . "'";
    $sql .= ")";
    
    try {
      $conn->exec($sql);
    } catch (Exception $e) {
      throw $e;
    }
    return $this->myid;
  }
  
  public function asRDF($timestamps=TRUE, $metadata=TRUE, $subject="", $whitesp="") {
    $bx = parent::asRDF($timestamps, $metadata, $subject, $whitesp);
    // trim ending bracket off poibasetype expression
    $ends = strrpos($bx, ']');
    if ( $ends !== FALSE ) {
      $bx = substr($bx, 0, $ends);
    }

    if ( $this->targetPOI != null ) {
      $bx .= "\n$whitesp\t$openpoitype:targetPOI\t" . $this->targetPOI . " ; ";
    }
    
    $bx .= $whitesp . '] ; ';
    return $bx;
  }
  
  public function asXML($timestamps=TRUE, $metadata=TRUE) {
    $bx = '';
    $bx .= '<' . strtolower($this->typename);
    if ( strlen($this->getXMLAttributeSnippet($timestamps, $metadata)) > 0 )
      $bx .= ' ' . trim( $this->getXMLAttributeSnippet() );
    $bx .= ">\n";
    $bx .= $this->getXMLElements($timestamps, $metadata);
    $bx .= '</' . strtolower($this->typename) . '>' . "\n";
    return $bx;
  }

  protected function getXMLAttributeSnippet($timestamps=TRUE, $metadata=TRUE) {
    $x = '';
    $x .= parent::getXMLAttributeSnippet($timestamps, $metadata);
    $x .= " targetPOI=\"" . htmlspecialchars($this->targetPOI) . "\"";
    return $x;    
  }
  
  public function getTargetPOI() {
    return $this->targetPOI;
  }

  public function setTargetPOI($tpoiurl) {
    if (validateURL($tpoiurl)) {
      $this->targetPOI = $tpoiurl;
      $changed = true;
      return TRUE;
    } else {
      return FALSE;
    }
  }
  
  function __construct($targetpoi=NULL, $term) {
    // parent::__construct('RELATIONSHIP', $term, NULL, 'http://www.opengis.net/poi/term/relationship');
    parent::__construct('RELATIONSHIP', $term, NULL);
    if ( $targetpoi == NULL ) {
      throw new Exception('TargetPOI is required.');
    }
    if ( !$this->setTargetPOI($targetpoi) ) {
      throw new Exception('TargetPOI must be a proper URI');
    }
  }
  
}

?>