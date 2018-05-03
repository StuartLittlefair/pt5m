<?php
header('Content-type: application/json');


#get status from GET or POST - return whole queue if
$havePID = array_key_exists('pID',$_REQUEST);
$PID = array_key_exists('pID',$_REQUEST) ? $_REQUEST['pID'] : '1';

// connect to DB
require("db.class.php");
// create instance of database class
$db = db_connection();

$queryString = "SELECT userID FROM `pointings` WHERE pointingID='" . $PID ."';";
if (!is_numeric($PID)){
  print json_encode("Invalid Query");
}else{
	$result = $db->query($queryString);

	$numRes = $result->num_rows;
	if($numRes == 0){
	  print json_encode("No Results Found");
	}
	else
	{
		$result->data_seek(0);
	  $rows =  $result->fetch_assoc();
	  print json_encode($rows);
	}
}
$db->close();
?>
