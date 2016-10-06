<?php session_start();

include('subs.php');

if(empty($_SESSION['u_name'])) {
  echo "Cannot delete pointings if not logged in";
  exit();
}

if(isset($_REQUEST['id']))
  {

    // connect to DB
    require("db.class.php");
    // create instance of database class
    $db = new mysqldb();
    $db->select_db();


    // get user for this pointing
    $result = $db->query("SELECT userID from `pointings` WHERE pointingID = " . $_REQUEST['id']);
    $row = $db->fetch_array($result);
    $uid = $row[0];
    $match = strcmp($uid,$_SESSION['u_name']);

    if(strcmp($uid,$_SESSION['u_name']) !=0 ){
      echo "This pointing belongs to " . $uid;
      exit();
    }

    // delete pointing and matching exposures
    $queryString1 = "UPDATE `pointings` SET status='deleted' WHERE pointingID = " . $_REQUEST['id'];
    $queryString2 = "DELETE from `exposures` WHERE pointingID = " . $_REQUEST['id'];

    // run deletion
    // update pointing
    $result = $db->query($queryString1);
    $result = $db->query($queryString2);
	  $db->kill();
    echo "OK";
  }
?>
