<?php
require_once('geonames.constants.php');
require_once('utils.php');
require_once('class.poi.php');

/**
* class.geonames.delete.php
* Deletes Geonames data from OpenPOI DB
*/
Class GeonamesDeleter {
  /**
  * Input is the Geonames ID. This function finds the POI that has 
  * that link, deletes that link property, and then deletes all other properties 
  * in that POI whose author is Geonames
  */
  public static function delete($geonameid) {
    global $geonamesbaseurl, $geonamesid;
    
    // build geonames objects
    //// author
    $ga = getGeonamesAuthor();

    // get POI with this link
    $poi = null;
    try {
      $pgconn = getDBConnection();      
      $pgconn->beginTransaction();

      $sql = "SELECT myid, parentid FROM poitermtype WHERE id LIKE '$geonameid' AND ";
      $sql .= "objname LIKE 'LINK' AND base LIKE '$geonamesbaseurl' AND deleted IS NULL";
      $c = $pgconn->query($sql);
      if ( $c ) {
        foreach ($c as $row) {
          $poi = POI::loadPOIUUID($row['parentid']);

          // delete the Geonames link
          $links = $poi->links;
          $k = sizeof($links);
          for ($j=0; $j<$k; $j++) {
            if ( $links[$j]->getId() == $geonameid && $links[$j]->getBase() == $geonamesbaseurl ) {
              $poi->removeObjectByIndex($poi->links, $j, $pgconn);
              break;
            }
          }

          // now delete all other properties with a GN author
          $labels = $poi->labels;
          $k = sizeof($labels);
          for ($j=0; $j<$k; $j++) {
            $a = $labels[$j]->getAuthor();
            if ( $a != null && $a->getId() == $geonamesid ) {
              $poi->removeObjectByIndex($poi->labels, $j, $pgconn);
            }
          }

          $descriptions = $poi->descriptions;
          $k = sizeof($descriptions);
          for ($j=0; $j<$k; $j++) {
            $a = $descriptions[$j]->getAuthor();
            if ( $a != null && $a->getId() == $geonamesid ) {
              $poi->removeObjectByIndex($poi->descriptions, $j, $pgconn);
            }
          }

          $categories = $poi->categories;
          $k = sizeof($categories);
          for ($j=0; $j<$k; $j++) {
            $a = $categories[$j]->getAuthor();
            if ( $a != null && $a->getId() == $geonamesid ) {
              $poi->removeObjectByIndex($poi->categories, $j, $pgconn);
            }
          }

          $times = $poi->times;
          $k = sizeof($times);
          for ($j=0; $j<$k; $j++) {
            $a = $times[$j]->getAuthor();
            if ( $a != null && $a->getId() == $geonamesid ) {
              $poi->removeObjectByIndex($poi->times, $j, $pgconn);
            }
          }

          $links = $poi->links;
          $k = sizeof($links);
          for ($j=0; $j<$k; $j++) {
            $a = $links[$j]->getAuthor();
            if ( $a != null && $a->getId() == $geonamesid ) {
              $poi->removeObjectByIndex($poi->links, $j, $pgconn);
            }
          }

          $pts = $poi->location->getPoints();
          $k = sizeof($pts);
          // if there's only one point, leave it out of mercy to the integrity of the POI
          // in case there's other data on this POI now
          if ( $k > 1 ) { 
            for ($j=0; $j<$k; $j++) {
              $a = $pts[$j]->getAuthor();
              if ( $a != null && $a->getId() == $geonamesid ) {
                $$poi->location->removeObjectByIndex($poi->location->getPoints(), $j, $pgconn);
              }
            }
          }

        } // end for each row
      } // end if query has stuff ($c)

      $pgconn->commit();
      $pgconn = null;
    } catch (Exception $e) {
      echo "geonames.delete.php FAIL with GN id $geonameid: " . $e->getMessage() . "\n";
      $pgconn = NULL;
    }
  }
}

?>
