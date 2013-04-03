<html>
<head>
<meta http-equiv="Content-Type" content="text/html; charset=iso-8859-1">
<title>poidb:: the open points of interest database</title>
<link href="../global.css" rel="stylesheet" type="text/css">
<style type="text/css">
<!--
body {
	margin-left: 50px;
}
-->
</style></head>

<body>
<h1>poidb: the open points of interest database</h1>
<h3>proudly serving <?php
$m = new Mongo(); // connect to mongodb
$mdb = $m->poidb; // select a database
$coll = $mdb->poi; // select a collection
echo $coll->count();
?> points of interest to the world</h3>

get a random poi document out of the db in <a href="getrandom.php">JSON</a> or <a href="getrandom.php?format=xml">XML</a>

</body>

</html>
