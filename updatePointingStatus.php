<?php
header('Content-type: application/json');

// connect to DB
require("db.class.php");
// create instance of database class
$db = new mysqldb();
$db->select_db();

$status = $_REQUEST['status'];
// can handle single values, or a list of values in square brackets...
$pid    = json_decode($_REQUEST['pID']);

$allowedStatusVals  = array('aborted','completed','interrupted','pending','defined','deleted','expired');
if ( count(array_intersect(array($status),$allowedStatusVals)) != 1)
{
print json_encode(False);
exit;
}

if (is_array($pid)){
	//print "ARRAY\n";
	$queryString = "UPDATE pointings SET status='" . $status . "' WHERE pointingID IN (";
	$queryString .= join(",",$pid) . ");";
}else{
	//print "NOT ARRAY\n";
	$queryString = "UPDATE pointings SET status='" . $status . "' WHERE pointingID='" . $pid . "'";
}

//print $queryString;
$result = $db->query($queryString); 
print json_encode($result);

$db->kill();
