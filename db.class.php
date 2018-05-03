<?php
function db_connection(){

	$hostname = "127.0.0.1";
	$username = "ph1spl_pt5m";
	$password = "X+K-2s43SQpt";
	$database = "ph1spl_pt5m";

	$mysqli = new mysqli($hostname, $username, $password, $database);
	if($mysqli->connect_errno){
		die("Failed to connect to MySQL: (" . $mysqli->connect_errno . ") " . $mysqli->connect_error);
	}
	return $mysqli;
}
?>
