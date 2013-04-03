<?php

include_once('utils.php');
include_once('class.matchcandidate.php');
include_once('String-Similarity.php');

/**
 * Takes an array of match candidates and looks up the parent locations, 
 * then looks up location's parent, which is the POI, then gets all labels for each POI, 
 * and assigns a name matching score.
 * @param $guessbest: if true, stop checking if you get a score above 0.9 (this makes 
 * sense especially if matches are passed in order of closest first, so that a match that's 
 * close in distance, that then scores above 90% for the name, is likely to be the right one)
 */
function getPOINames($name, $matches, $guessbest=FALSE, $levchanges=5) {
  if ( empty($matches) ) return NULL;
  $newmatches = array();
  $comp = new StringMatch();
  
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
              //// do string comparison
              // echo "VALUE is: $v AND NAME IS: $name\n";
              // $result = $comp->fstrcmp($name, strlen($name), $v, strlen($v), $minscore);
              // echo "$name matched against $v>> result: $result | minscore: $minscore | distance: $m->dist\n";
              
              //// The Levenshtein distance is defined as the minimal number of characters you have to 
              //// replace, insert or delete to transform str1 into str2. 
              //// The complexity of the algorithm is O(m*n), where n and m are the length of str1 and str2
              $result = levenshtein($name, $v);
              // echo "$name matched against $v>> \nresult: $result | distance: $m->dist\n";
              
              // if ( $result >= $minscore ) {
              if ( $result < $levchanges ) {
                // SCALE RESULT TO A VALUE FROM 0 to 1 WHERE 1=levchangesx4 and 0=no change
                $result = $result / ( $levchanges * 4 );
                $names[$v] = $result;
                // echo "got label: $v\tscore: $result\n";
                $foundone = true;
              }
            }
            $m->labels = $names;
            if ( $foundone ) $newmatches[] = $m;

            // if we want to try to use a shortcut and return a really close match as soon 
            // as it's found....
            if ( $guessbest && $result < 2 ) {
              $mg = array();
              $mg[] = $m;
              return $mg;
            }
          } // end if d (labels exist)
        } // end for each row in location query (should only be 1)
      } // end if any location with the right id
    } // end looping on matches
    $conn = NULL;
  } catch (Exception $e) {
    echo "getGeoNames() failed: " . $e->getMessage() . "\n";
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
        $m->dist = $row['dist'];
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