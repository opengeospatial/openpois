<?php

$b = "http://example.com";
$i = $b . "/3242324";
$j = str_replace($b, "", $i);
$j = trim($j, "/");
echo "full id: $i\n";
echo "new id: $j\n";
?>