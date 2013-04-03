<?php
include_once('../conflation/String-Similarity.php');

$name = 'Faneuil Hall';
$tests = array('Ancient & Honorable Artillery Co of Massachusetts', 'Bell in Hand Tavern', 'Boston Pewter Company', 'Faneuil Hall', 'Green Dragon','Millennium Bostonian Hotel','New England Holocaust Memorial','Out of Left Field','Union Oyster House');

$comp = new StringMatch;
foreach ($tests as $t) {
  $m = $comp->fstrcmp($name, strlen($name), $t, strlen($t), 0.5);
  echo "score for $t is: $m\n";
}

?>