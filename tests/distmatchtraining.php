<?php
include_once('../utils.php');
include_once('../conflation/String-Similarity.php');
include_once('../conflation/class.matchcandidate.php');

$targetpt = 'SRID=4326;POINT(-71.13410 42.39506)';
$targetlbl = 'testmeplease';

$testpts = array();
$testpts[] = 'SRID=4326;POINT(-71.134050 42.395063)';
$testpts[] = 'SRID=4326;POINT(-71.133460 42.395063)';
$testpts[] = 'SRID=4326;POINT(-71.132337 42.395063)';
$testpts[] = 'SRID=4326;POINT(-71.130572 42.395063)';
$testpts[] = 'SRID=4326;POINT(-71.127763 42.395063)';

$testlbls = array();
$testlbls[] = 'testmeplease';
$testlbls[] = 'testmepleez';
$testlbls[] = 'testmepleese';
$testlbls[] = 'tetmeplease';
$testlbls[] = 'test me pleasee';

try {
  $pgconn = getDBConnection();
  $comp = new StringMatch;
  $matches[] = array();

  $i = 0;
  $match = 999999.0;
  
  while ($i<5) {
    $m = new MatchCandidate($i);

    $lbl = $testlbls[$i];
    $lblscore = $comp->fstrcmp( $targetlbl, strlen($targetlbl), $lbl, strlen($lbl) );
    $n = array();
    $n[$lbl] = $lblscore;
    $m->labels = $n;
    
    $pt = $testpts[$i];
    $sql = "SELECT ST_DISTANCE( ST_GeographyFromText('$targetpt'), ST_GeographyFromText('$pt') ) AS dist";
    $c = $pgconn->query($sql);
    if ( $c ) {
      foreach ( $c as $row) {
        $m->dist = $row['dist'];
      }
    }
    $m->computeScore(250);
    
    echo "DIST: $m->dist\tSTRING SIM: $lblscore\tSCORE: $m->score\n";
    
    $i++;
  }
} catch (Exception $e) {
  echo "failed: " . $e->getMessage() . "\n";
  echo "$sql\n";
}

?>