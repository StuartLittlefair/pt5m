<?php
header('Content-type: application/json');
date_default_timezone_set("GMT");
$today = date('Y-m-d');

#get status from GET or POST - return whole queue if
$haveStatus = array_key_exists('status',$_REQUEST);
$status = array_key_exists('status',$_REQUEST) ? $_REQUEST['status'] : 'defined';

//check for list of pointing IDs, if present return only this list
// can handle single values, or a list of values in square brackets...
$havePID = array_key_exists('pID',$_REQUEST);
$pid    = json_decode($_REQUEST['pID']);

// connect to DB
require("db.class.php");
// create instance of database class
$db = db_connection();

if($havePID){
	if($haveStatus){
		$queryString = "SELECT * FROM `pointings` WHERE status='" . $status ."' AND pointingID IN (";
	}else{
		$queryString = "SELECT * FROM `pointings` WHERE 1 AND pointingID IN (";
	}
	if (is_array($pid)){
		$queryString .= join(",",$pid) . ");";
	}else{
		$queryString .= $pid . ");";
	}
}else{
	if($haveStatus){
		$queryString = "SELECT * FROM `pointings` WHERE status='" . $status ."';";
	}else{
		$queryString = "SELECT * FROM `pointings` WHERE 1;";
	}
}
$result = $db->query($queryString);

$numRes = $result->num_rows;
if($numRes == 0){
  print json_encode("No Results Found");
}
else
{
  $rows = array();
  $result->data_seek(0);
  while($pointing = $result->fetch_assoc())
  {
	$queryString = "SELECT * FROM `exposures` WHERE pointingID = " . $pointing['pointingID'];

	$resultExp = $db->query($queryString);
	$numRes = $resultExp->num_rows;
	#if($numRes == 0) continue; //skip pointings with no exposures

	// fill array with exposures for this pointing
	$exposures = array();
	$resultExp->data_seek(0);
	while($exp = $resultExp->fetch_assoc())
	{
		$exposures[] = $exp;
	}
	$rows[] = array('pointing' => $pointing,
					'exposures' => $exposures);

  }
  print json_encode($rows);
}
$db->close();
?>
