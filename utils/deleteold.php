<?php

$days = 2;
$dir = '/tmp/test';
	if ($handle = opendir($dir)) {
	while (false !== ($file = readdir($handle))) {
		if ($file[0] == '.' || is_dir($dir.'/'.$file)) {
			continue;
		}
		if ((time() - filemtime($dir.'/'.$file)) > ($days *86400)) {
			unlink($dir.'/'.$file);
		}
	}
	closedir($handle);
}
?>