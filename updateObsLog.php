<?php
header('Content-type: application/json');

// connect to DB
require("db.class.php");
// create instance of database class
$db = new mysqldb();
$db->select_db();

// get encoding of observations from POST
$decode = stripslashes($_REQUEST['jsonObsList']);
$arr = json_decode($decode,true) or die("Can't decode obsList");

//check everything defined for each observation
$dontWantTheseHere  = array(NULL);
foreach($arr as $obs){
	if ( count(array_intersect($obs,$dontWantTheseHere)) >= 1)
	{
		$myArgs["success"] = False;
		$myArgs["reason"] = "not all params supplied";
		print json_encode($myArgs);
		exit;
	}
}

$queryString = mysql_insert_observations('obslog',$arr);
$result = $db->query($queryString);

print json_encode($result);

$db->kill();

/////////////////////////////////////////////////////////////////////
function mysql_insert_observations($table,$obsArr){
	$firstObs = $obsArr[0];
	foreach ($firstObs as $field=>$value){
		$fields[] = '`' . $field . '`';
	}
	$field_list = join(',',$fields);
	
	$valuesList = "";
	foreach($obsArr as $obs){
		$values = NULL;
		foreach($obs as $field=>$value){
			$values[]  = "'" . mysql_real_escape_string($value) . "'";
		}
		$valuesList .= "(" . join(", ",$values) . "),";
	}
	// strip trailing comma
	$valuesList = trim($valuesList,",");
	$query = "INSERT IGNORE INTO `" . $table . "` (" . $field_list . ") VALUES " . $valuesList . ";";
	return $query;
}
?>

