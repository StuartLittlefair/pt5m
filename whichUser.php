<?php
header('Content-type: application/json');


#get status from GET or POST - return whole queue if
$havePID = array_key_exists('pID',$_REQUEST);
$PID = array_key_exists('pID',$_REQUEST) ? $_REQUEST['pID'] : '1';

// connect to DB
require("db.class.php");
// create instance of database class
$db = new mysqldb();
$db->select_db();


$queryString = "SELECT userID FROM `pointings` WHERE pointingID='" . $PID ."';";
if (!is_numeric($PID)){
  print json_encode("Invalid Query");
}else{
	$result = $db->query($queryString);

	$numRes = $db->num_rows($result);
	if($numRes == 0){
	  print json_encode("No Results Found");
	}
	else
	{
	  $rows =  $db->fetch_assoc($result);
	  print json_encode($rows);
	}
}
$db->kill();
?>
