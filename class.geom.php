<?php
require_once('constants.php');

Class Geom extends POITermType {
  protected $geomtypename;
  // default is http://www.opengis.net/def/crs/EPSG/0/4326
  // FYI 3D lon lat is code http://www.opengis.net/def/crs/EPSG/0/4269
  public $srsname = NULL; 
  public $poslist = NULL; 
    
  static function validateCoordinates($poslist) {
    $ps = explode(' ', $poslist);
    foreach ($ps as $p) {
      if ( !(is_numeric($p)) ) return FALSE;
    }
    return TRUE;
  }

  /**
   * Copy data from the <Point>, <LineString>, or <SimplePolygon> SimpleXML object to Geom class variables
   * @param xml 
   * @param typename POINT, LINE or POLYGON but should be auto-detected. Use this to override
   */
  static function loadXMLData($xml, $typename=NULL, $geomobj=NULL, $author=NULL) {
    $term = NULL;
    if ( !empty($xml['term']) ) $term = $xml['term'];
    $scheme = NULL;
    if ( !empty($xml['scheme']) ) $scheme = $xml['scheme'];
    $srsname = NULL;
    if ( !empty($xml->srsName) ) $srsname = $xml->srsName;
    if ( !empty($xml['srsName']) ) $srsname = $xml['srsName'];
    $value = NULL;
    if ( !empty($xml->value) ) $value = $xml->value;

    
    $geomtype = $xml->getName();
    
    if ( empty($typename) ) {
      $typename = 'POINT';

      switch( $geomtype ) {
      case 'LineString': 
        $typename = 'LINE';
        break;
      case 'SimplePolygon': 
        $typename = 'POLYGON';
        break;
      }
    } else {
      $typename = strtoupper($typename);
    }
    
    $poslist = NULL;
    foreach ($xml->xpath('//posList') as $p) { // should be only 1 posList element but iterate anyway
      $poslist = (string)$p;
    }
    $ok = Geom::validateCoordinates($poslist);
    if ( !$ok ) {
      echo ("BAD <posList> in $typename!\n" . var_dump($xml) . "\n");
      return FALSE;
    }

    if ( $geomobj == NULL ) 
      $geomobj = new Geom($typename, $geomtype, $poslist, $term, $srsname, $value, $scheme);
    else {
      $geomobj->srsname = $srsname;
      $geomobj->poslist = $poslist;
    }
    $geomobj = POITermType::loadXMLData($xml, NULL, $geomobj, $author);
    $geomobj->changed = true;

    return $geomobj;
  }
  
  /**
   * Copy data from geo database table to Geom class variables
   * @param PDO array of a record from geo database table $row
   * @param connection object to use for possible further querying $conn
   */
  static function loadDBData($row, $conn, $geoobj=NULL) {
    if ( $geoobj == NULL ) {
      $geoobj = new Geom($row['objname'], $row['geomtype'], $row['nativecoords'], $row['term'], $row['nativesrsuri'], $row['value'], $row['scheme']);
    }
    $geoobj = POITermType::loadDBData($row, $conn, $geoobj);
    return $geoobj;
  }
  
  public function updateDB($parentuuid, $conn) {
    return $this->insertDB($parentuuid, $conn);
  }

  /**
   * $parentuuid: parent (the poi) element's uuid 
   * $conn: database connection
   * @TODO linestring and polygon inserts won't work -- need commas between coord pairs
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

    $sql = "insert into geo";
    $sql .= " (myid,parentid,objname,id,value,href,type,authorid,licenseid,lang,base,created";
    $sql .= ",term,scheme,geomtype,nativesrsuri,nativecoords,geompt,geomline,geompoly";
    $sql .= ") values (";
    $sql .= parent::getDBInsertValues($conn);
    $sql .= ",'" . $this->term . "'";
    $sql .= "," . $this->ce($this->scheme);
    $sql .= ",'" . $this->geomtypename . "'";
    $sql .= ",'" . $this->srsname . "'";
    $sql .= ",'" . $this->poslist . "'";

    // reverse the coordinates from YX to XY (because PostGIS incorrectly handles EPSG:4326 as XY)
    // THIS CODE ONLY WORKS FOR 2D POINTS RIGHT NOW!!!!!!!
    $cs = explode(' ', $this->poslist);
    $coords = $cs[1] . ' ' . $cs[0];

    $srid = substr( $this->srsname, strrpos($this->srsname, "/")+1 );
    if (empty($srid)) $srid = '4326';
    if ( strcasecmp($this->geomtypename, 'Point') == 0 ) {
      $sql .= ",ST_GeomFromText('POINT($coords)', $srid)";
      // $sql .= ",ST_GeographyFromText('SRID=4326;POINT($this->poslist)')";
    } else {
          $sql .= ",NULL";
    }
    if ( strcasecmp($this->geomtypename, 'LineString') == 0 ) {
      $sql .= ",ST_GeomFromText('LINESTRING($this->poslist)', $srid)";
      // $sql .= ",ST_GeographyFromText('SRID=4326;LINESTRING($this->poslist)')";
    } else {
      $sql .= ",NULL";
    }
    if ( strcasecmp($this->geomtypename, 'Polygon') == 0 ) {
      $sql .= ",ST_GeomFromText('POLYGON($this->poslist)', $srid)";
      // $sql .= ",ST_GeographyFromText('SRID=4326;POLYGON($this->poslist)')";
    } else {
      $sql .= ",NULL";
    }

    $sql .= ")";    
    // echo "$sql\n";
    
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

    if ( $this->poslist != null ) {
      $bx .= "$whitesp   $openpoitype:poslist " . $this->poslist . " ; ";
    }
    if ( $this->srsname != null ) {
      $bx .= "\n$whitesp  $openpoitype:srsname " . $this->srsname . " ; ";
    }
    
    $bx .= "\n" . $whitesp . '] . ';
    return $bx;
  }
  
  public function asXML($timestamps=TRUE, $metadata=TRUE) {
    $bx = '';
    $bx .= '<' . strtolower($this->typename);
    if ( strlen($this->getXMLAttributeSnippet($timestamps, $metadata)) > 0 )
      $bx .= ' ' . trim( $this->getXMLAttributeSnippet($timestamps, $metadata) );
    $bx .= ">\n";

    $bx .= $this->getXMLElements($timestamps, $metadata);
    // Point, LineString, or Polygon here
    $bx .= '<' . $this->geomtypename;
    if ( $this->srsname != NULL && $this->srsname != "http://www.opengis.net/def/crs/EPSG/0/4326" ) 
      $bx .= 'srsName="' . htmlspecialchars($this->srsname) . '">' . "\n";
    else 
      $bx .= '>' . "\n";
    $bx .= '<posList>' . $this->poslist . '</posList>' . "\n";
    $bx .= '</' . $this->geomtypename . '>' . "\n";
    // end Point, LineString, or Polygon
    $bx .= '</' . strtolower($this->typename) . '>' . "\n";
    return $bx;
  }
  
  public function getSRSName() {
    return $this->srsname;
  }

  public function setSRSName($srs) {
    $this->srsname = $srs;
    $changed = true;
  }

  public function getPosList() {
    return $this->poslist;
  }

  public function setPosList($c) {
    $this->poslist = $c;
    $changed = true;
  }
  
  function __construct($typename, $geomtypename, $poslist, $term, $srsname=NULL, $value=NULL, $scheme=NULL) {
    parent::__construct($typename, $term, $value, $scheme);
    $this->geomtypename = $geomtypename;
    $this->poslist = $poslist;
    if ( $srsname == NULL || validateURL($srsname) )
      $this->srsname = $srsname;
    else 
      throw new Exception("BAD srsname!\n");
  }
  
}

?>