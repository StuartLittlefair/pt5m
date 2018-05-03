<?php session_start();

include('subs.php');

if(empty($_SESSION['u_name'])) {
  echo "Cannot delete exposures if not logged in";
  exit();
}

if(isset($_REQUEST['id']))
  {
    // connect to DB
    require("db.class.php");
    // create instance of database class
    $db = db_connection();

    // get user for this pointing
    $result = $db->query("SELECT userID from `pointings` WHERE pointingID = " . $_REQUEST['pID']);
    if ($result->num_rows == 0){
      echo "No such pointing";
      exit();
    }
    $result->data_seek(0);
    $row = $result->fetch_array();
    $uid = $row[0];
    $match = strcmp($uid, $_SESSION['u_name']);

    if(strcmp($uid, $_SESSION['u_name']) !=0 ){
      echo "This pointing belongs to " . $uid;
      exit();
    }

    // delete pointing and matching exposures
    $queryString = "DELETE from `exposures` WHERE id = " . $_REQUEST['id'];

    // run deletion
    // update pointing
    $result = $db->query($queryString);

    echo "OK";
  }
  $db->close();
?>
