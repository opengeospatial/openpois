<?php

include_once('utils.php');
include_once('class.matchcandidate.php');

/**
 * Takes an array of match candidates and looks up the parent locations, 
 * then looks up location's parent, which is the POI, then gets all labels for each POI, 
 * and assigns a name matching score.
 * @param $guessbest: if true, stop checking if you get a score above 0.9 (this makes 
 * sense especially if matches are passed in order of closest first, so that a match that's 
 * close in distance, that then scores above 90% for the name, is likely to be the right one)
 */
function getPOINames($name, $matches, $guessbest=FALSE, $levmaxratio=0.20) {
  if ( empty($matches) ) return NULL;
  $newmatches = array();
  
  try {
    $pgconn = getDBConnection();
    
    foreach ($matches as $m) {
      $l = $m->locuuid;
      $sql = "select parentid from location where myid = '$l'";
      // echo "SQL: $sql\n";exit;
      $c = $pgconn->query($sql);
      if ( $c ) {
        foreach ($c as $row) { 
          $names = array();
          $foundone = false;
          // got location's parent id
          $puuid = $row['parentid'];
          $m->poiuuid = $puuid;

          // if no name was passed, don't do name match, just stop at adding the poiuuid to the match candidate
          if ( empty($name) ) {
            continue;
          }

          // now get LABELs with the same parent uuid
          $sql = "select value from poitermtype where parentid = '$puuid' and objname like 'LABEL'";
          $d = $pgconn->query($sql);
          $result = 0.0;
          if ( $d ) {
            foreach ($d as $lrow) { 
              $v = $lrow['value'];
              
              //// The Levenshtein distance is defined as the minimal number of characters you have to 
              //// replace, insert or delete to transform str1 into str2. 
              $result = levenshtein($name, $v);
							//// now scale it by the number of characters in the original name
							//// (note: this value may be greater than 1 if length of input string is greater than 
							//// length of original, and # of characters to change is greater than original)
							$result = $result / strlen($name);
              // echo "$name matched against $v>> \nresult: $result | distance: $m->dist\n";
              
              // if ( $result >= $minscore ) {
              if ( $result < $levmaxratio ) {
                $names[$v] = $result;
                $foundone = true;
                // echo "got label: $v\tscore: $result\n";
              }
            }
            $m->labels = $names;
            if ( $foundone ) $newmatches[] = $m;

            // if we want to try to use a shortcut and return a really close match as soon 
            // as it's found....
            if ( $guessbest && $result < 0.010 ) return array($m);
          } // end if d (labels exist)
        } // end for each row in location query (should only be 1)
      } // end if any location with the right id
    } // end looping on matches
    $conn = NULL;
  } catch (Exception $e) {
    echo "conflate->getPOINames() failed: " . $e->getMessage() . "\n";
    return NULL; // successful loading is false
  }
  
  return $newmatches;
}

/**
 * @param dist is distance in meters
 * @param limit number of results to return (pass NULL (the default) if no limit)
 * @return array of MatchCandidate objects in order from closest to farthest
 * NOTE: This only works if the data is in EPSG:4326
 */
function getDistanceMatches($lon, $lat, $dist=500, $limit=NULL) {
  $matches = array();
  $pt = "ST_GeographyFromText('SRID=4326;POINT($lon $lat)')";
  //// next 4 lines are pre-20130318
  // $sql = "SELECT myid, parentid, ST_AsText(geompt) AS GEOM, ";
  // $sql .= "ST_Distance(Geography(ST_Transform(geompt,4326)), $pt) AS dist FROM geo";
  // $bbox = buildBBox($lat, $lon, $dist);
  // $sql .= " WHERE geompt && ST_SetSRID(ST_MakeBox2D(ST_Point($bbox[0], $bbox[1]),ST_Point($bbox[2], $bbox[3])),4326)";
  
  $sql = "SELECT myid, parentid, ST_Distance(geogpt, $pt) AS dist FROM geo";
  $sql .= " WHERE ST_DWithin(geogpt, $pt, $dist)";
  $sql .= " ORDER BY dist ASC";
  if ( !empty($limit) ) $sql .= " LIMIT $limit";
  // echo "getDistanceMatches SQL: $sql\n";exit;
  
  try {
    $pgconn = getDBConnection();
    $c = $pgconn->query($sql);
    if ( $c ) {
      foreach ( $c as $row) {
        $m = new MatchCandidate($row['myid'], $dist);
        $m->locuuid = $row['parentid'];
        $m->dist = floatVal($row['dist']);
        $matches[] = $m;
      }
    } else {
      return NULL;
    }
    
  } catch (Exception $e) {
    echo "getDistanceMatches() failed: " . $e->getMessage() . "\n";
    echo "$sql\n";
    return NULL;
  }
  return $matches;
}

?>