<?php
require_once('constants.php');
require_once('geonames.constants.php');
require_once('utils.php');

/**
 * geonames.dropauthor.php
 */
$copydir = '/Users/rajsingh/workspace/openpoidb/databases/tmp/'; // local
$authorid = '88f72b90-5cbe-4a7a-b1c1-6c9d83cd3998';

// $copydir = '/var/www/db/import/'; // production
$fp = fopen($copydir.'poitermtype.in', "r");
$poitermtypefp = fopen($copydir.'poitermtype.out', "w");

if ( FALSE === $poitermtypefp || FALSE === $fp ) {
  die("couldn't open file!!!\n");
}

$i = 0;
while ( ($line=fgets($fp) ) != false ) {
  $i++;
  $vs = explode("\t", $line);
  
  $objtype = $vs[2];
  if ( $objtype == 'AUTHOR' ) continue;
  
  fwrite($poitermtypefp, $line);
} // end while

fclose($poitermtypefp);
fclose($fp);
// copy poitermtype from '/var/www/db/import/poitermtype.in';

?>
