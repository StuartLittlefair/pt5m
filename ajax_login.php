<?php session_start();
//  Developed by Roshan Bhattarai
//  Visit http://roshanbh.com.np for this script and more.
//  This notice MUST stay intact for legal use

//Connect to database from here
require("db.class.php");
//create instance of database class
$db = new mysqldb();
$db->select_db();

//get the posted values
$user_name=htmlspecialchars($_POST['user_name'],ENT_QUOTES);
$pass=md5($_POST['password']);

//now validating the username and password
$sql="SELECT user_name, password FROM tbl_user WHERE user_name='".$user_name."'";
$result=$db->query($sql);
$row=$db->fetch_array($result);

//if username exists
if($db->num_rows($result)>0)
{
	//compare the password
	if(strcmp($row['password'],$pass)==0)
	{
		echo "yes";
		//now set the session from here if needed
		$_SESSION['u_name']=$user_name;
	}
	else
		echo "no";
}
else
	echo "no"; //Invalid Login
?>
