<?php
/**
 * geonames.update.php
 * Downloads daily modification and deletion files from Geonames server and runs 
 * update and deletion scripts
 */
date_default_timezone_set('America/New_York');
$d = new DateTime();
$di = DateInterval::createFromDateString('1 day');
$d = $d->sub($di);
$t = $d->format('Y-m-d');

$gndownloaddir = '/srv/openpoidb/databases/tmp/';
// $gndownloaddir = '/Users/rajsingh/workspace/openpoidb/databases/tmp/';
$gndownloadbase = 'http://download.geonames.org/export/dump/';
$gndownloadmod = 'modifications-' . $t . '.txt';
$gndownloaddel = 'deletes-' . $t . '.txt';

$url = $gndownloadbase . $gndownloaddel;
$delfile = $gndownloaddir . $gndownloaddel;
$fp = fopen($delfile , 'w');
$ch = curl_init($url);
curl_setopt($ch, CURLOPT_FILE, $fp);
$data = curl_exec($ch);
curl_close($ch);
fclose($fp);

$url = $gndownloadbase . $gndownloadmod;
$modfile = $gndownloaddir . $gndownloadmod;
$fp = fopen($modfile , 'w');
$ch = curl_init($url);
curl_setopt($ch, CURLOPT_FILE, $fp);
$data = curl_exec($ch);
curl_close($ch);
fclose($fp);

// now process the files
system("php /srv/openpoidb/application/geonames/geonames.delete.php $delfile");
system("php /srv/openpoidb/application/geonames/geonames.modify.php $modfile");

?>
