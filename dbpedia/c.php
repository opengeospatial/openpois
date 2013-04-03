<?php
$u = "http://dbpedia.org/data/Casa_Mil%C3%A0.json";
// $u = "http://dbpedia.org/data/Banca_d%27Italia.json";

if ( $json = curl_get_file_contents($u) ) {
  $r = json_decode($json, true);
  echo "content:\n$json\n";
} else {
  echo "curl_get_file_contents returned false\n";
}

function curl_get_file_contents($url) {
  $c = curl_init();
  curl_setopt($c, CURLOPT_RETURNTRANSFER, TRUE);
  curl_setopt($c, CURLOPT_HEADER, FALSE);
  curl_setopt($c, CURLOPT_FOLLOWLOCATION, TRUE);
  curl_setopt($c, CURLOPT_USERAGENT, "spider");
  curl_setopt($c, CURLOPT_AUTOREFERER, TRUE);
  curl_setopt($c, CURLOPT_CONNECTTIMEOUT, 120);
  curl_setopt($c, CURLOPT_TIMEOUT, 120);
  curl_setopt($c, CURLOPT_MAXREDIRS, 9);
  curl_setopt($c, CURLOPT_URL, $url);
  $contents = curl_exec($c);
  curl_close($c);
  
  if ($contents) return $contents;
      else return FALSE;
}

?>