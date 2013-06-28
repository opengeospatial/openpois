<?php

$str1 = "Kent County Memorial Hospital Library";
$str2 = "Kent County Memorial Hospital Heliport";

$n = levenshtein($str1, $str2);
echo "lev: $n\n";
$n = $n / strlen($str1);

echo "weighted lev: $n\n";

?>