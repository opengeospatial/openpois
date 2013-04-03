<?php
//Set error level
error_reporting (E_ERROR);

//best run this script in CLI: 'php test.php'
require_once('FactualTest.php');

//Add your key and secret here
$key = "";
$secret = "";

//Add filename for to log results to file, if required (echoes to screen by default)
$logFile = "";
	
//Run tests	
$factualTest = new factualTest($key,$secret);	
$factualTest->setLogFile($logFile);   
$factualTest->test();

?>
