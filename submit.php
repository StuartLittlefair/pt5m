<?php session_start();
//Include the database class
require("db.local.class.php");
// prevent browsers from caching results
header( "Expires: Mon, 20 Dec 1998 01:00:00 GMT" );
header( "Last-Modified: " . gmdate("D, d M Y H:i:s") . " GMT" );
header( "Cache-Control: no-cache, must-revalidate" );
header( "Pragma: no-cache" );
include("subs.php");
?>

<!DOCTYPE html>
<html class="no-js">
<head>
    <meta charset="utf-8">
    <meta http-equiv="x-ua-compatible" content="ie=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>pt5m observation submission</title>
    <link rel="stylesheet" href="css/foundation.css">
    <link rel="stylesheet" href="css/app.css">
</head>

<body>
<div class="top-bar">
<div class="top-bar-left">
<ul class="dropdown menu" data-dropdown-menu>
    <li class="menu-text">pt5m Queue</li>
    <li><a href="index.php">login</a></li>
    <li><a href="submit.html">submit</a></li>
    <li><a href="view.html">view/edit</a></li>
</ul>
</div>
</div>

<div class='callout secondary'>

<?php
if(empty($_SESSION['u_name'])) {
  send_error_retry("Cannot submit observations if not logged in","submit.html");
  exit();
}

?>

<?php
//If form was submitted
if (isset($_POST['formSubmit'])) {

  $count = 0;
  if ($_POST) {
    foreach ($_POST as $key => $value) {
      if (substr_count($key,"type") > 0){
	$count++;
      }
    }
  }
  $count--;

  //create instance of database class
  $db = new mysqldb();
  $db->select_db();

  // parse values where necessary
  // NOTE: this section needs updating to block security holes, which are currently gaping
  // see http://articles.sitepoint.com/article/php-security-blunders
  // number one, are we SLODAR or QSI? If SLODAR we fake most things up
  $mode = $_POST['obstype'];

  // make SQL dateTime from Date and Time entries
  $startUT = $_POST['startUTC'];
  $endUT = $_POST['endUTC'];

  // check it's valid
  $format = 'd-m-Y H:i';
  $begin = new DateTime($startUT,new DateTimeZone('UTC'));
  $end   = new DateTime($endUT,new DateTimeZone('UTC'));
  if($end < $begin){
    send_error_retry("Invalid submission: end date before start date", "submit.html");
  	exit();
  }

  // sanitize possible ra, dec formats
  //$ra = str_replace(" ",":",$_POST['ra']);
  //$dec = str_replace(" ",":",$_POST['dec']);
  //print_r($_POST);
  if($mode == "SLODAR"){
    $ra = 0.0;
    $dec = 0.0;
  }else{
    $ra  = degrees($_POST['ra'], "hours");
    $dec = degrees($_POST['dec'], "degrees");
  }

  // sanitise moon brightness
  $moon = "B";
  if($_POST['maxMoon'] == "Dark"){
    $moon = "D";
  } elseif($_POST['maxMoon'] == "Grey"){
    $moon = "G";
  }

  // parse pier flip
  $flip = 0;
  if($_POST['flip'] == "on"){
    $flip = 1;
  }

  // parse time critical flag
  $ToO = 0;
  if($_POST['ToO'] == "on"){
  	$ToO = 1;
  }

  // parse guiding flag
  $guide = 0;
  if($_POST['guide'] == "on"){
    $guide = 1;
  }

  $status="defined";

  // get the others
  if($mode == "SLODAR"){
     $object="SLODAR";
     $minAlt=0.0;
     $minTime=0.0;
  }else{
     $object = $_POST['object'];
     $minAlt = $_POST['minAlt'];
     $minTime = $_POST['minTime'];
  }
  $priority = $_POST['priority'];
  $user    = $_SESSION['u_name'];

  //Insert static values into users table

  $sql_queue = sprintf("INSERT INTO `pointings` (object, RA, decl, priority, minAlt, minTime, maxMoon, userID, startUTC, stopUTC, status, pierFlip, ToO, guide) VALUES ('%s','%f','%f','%d','%01.2f','%01.2f','%s','%s','%s','%s','%s','%d','%d','%d')",
		       mysql_real_escape_string($object),
		       mysql_real_escape_string($ra),
		       mysql_real_escape_string($dec),
		       mysql_real_escape_string($priority),
		       mysql_real_escape_string($minAlt),
		       mysql_real_escape_string($minTime),
		       mysql_real_escape_string($moon),
		       mysql_real_escape_string($user),
		       mysql_real_escape_string($startUT),
		       mysql_real_escape_string($endUT),
		       mysql_real_escape_string($status),
		       mysql_real_escape_string($flip),
		       mysql_real_escape_string($ToO),
		       mysql_real_escape_string($guide) );
  $result_queue = $db->query($sql_queue);
  // get pointingID
  $inserted_pointing_id = $db->last_insert_id();

  // how many exposures to add?

  $expArr = array();
  for($i=1; $i <= $count; $i++) {
    $type = "type" . $i;
    $filter = "filter" .$i;
    $binning = "binning" .$i;
    $etime = "etime" .$i;
    $numexp = "numexp" .$i;

    $expArr[] = array("type" => $_POST[$type],
		      "filter" => $_POST[$filter],
		      "binning" => $_POST[$binning],
		      "etime" => $_POST[$etime],
		      "numexp" => $_POST[$numexp]);

    $currExp = end($expArr);


    $sql_exp = sprintf("INSERT INTO exposures (pointingID, typeFlag, filter, exptime, numexp, binning) VALUES ('%d','%s','%s','%.2f','%d','%d')",
		       mysql_real_escape_string($inserted_pointing_id),
		       mysql_real_escape_string($_POST[$type]),
		       mysql_real_escape_string($_POST[$filter]),
		       mysql_real_escape_string($_POST[$etime]),
		       mysql_real_escape_string($_POST[$numexp]),
		       mysql_real_escape_string($_POST[$binning]) );
    $result_exp = $db->query($sql_exp);

  }

  //disconnect mysql connection
  $db->kill();

  // put ra and dec back into human readable strings
  $ra = raString($ra);
  $dec = decString($dec);
 }
?>


<div class="row">
<div class="columns small-12">

  <?php echo "<h3>Submitted pointing number $inserted_pointing_id towards $object with $count exposures</h3>"; ?>
  <?php echo "<p>Click <a href='submit.html'>here</a> to submit another pointing</p>"; ?>

  <div class="table-scroll">
  <table class="hover" id="pointing" cellspacing="0">
  <caption>Pointing Details</caption>
  <thead>
  <tr>
    <th scope="col" abbr="Object" class="top-left">Object</th>
    <th scope="col" abbr="RA">RA (J2000)</th>
    <th scope="col" abbr="Dec">Dec (J2000)</th>
    <th scope="col" abbr="Priority">Pri</th>
    <th scope="col" abbr="Crit">TC</th>
    <th scope="col" abbr="Guide">Guide</th>
    <th scope="col" abbr="Min Alt">Min. Alt. (deg)</th>
    <th scope="col" abbr="Min Time">Min. Time (sec)</th>
    <th scope="col" abbr="Moon">Moon</th>
    <th scope="col" abbr="User">User ID</th>
    <th scope="col" abbr="Start">Start UTC</th>
    <th scope="col" abbr="Stop" class="top-right">Stop UTC</th>
  </tr>
  </thead>
  <tfoot>
  <tr>
  <td colspan="11" class="rounded-foot-left"><em>Please check these details carefully; use view/edit tab to change if necessary</em></td>
  <td class="rounded-foot-right">&nbsp;</td>
  </tr>
  </tfoot>
  <tbody>
  <tr>
  <?php
  echo "<td class='wide-col'>$object</td>";
  echo "<td class='wide-col'>$ra</td>";
  echo "<td class='wide-col'>$dec</td>";
  echo "<td>$priority</td>";
  if($ToO == 1){
  echo "<td>yes</td>";
  }else{
  echo "<td>no</td>";
  }
  if($guide == 1){
  echo "<td>yes</td>";
  }else{
  echo "<td>no</td>";
  }
  echo "<td>$minAlt</td>";
  echo "<td>$minTime</td>";
  echo "<td>$moon</td>";
  echo "<td>$user</td>";
  echo "<td class='wide-col'>$startUT</td>";
  echo "<td class='wide-col'>$endUT</td>";
  ?>
  </tr>
  </tbody>
  </table>
</div>
</div>
</div>

<div class="row">
<div class="columns small-12">
  <table class="hover stack" id="exposures" cellspacing="0">
  <caption>Exposures submitted for this pointing</caption>
  <thead>
  <tr>
    <th scope="col" abbr="type" class="top-left">Observation Type</th>
    <th scope="col" abbr="filter">Filter</th>
    <th scope="col" abbr="Bin">Binning</th>
    <th scope="col" abbr="Exp Time">Exposure Time</th>
    <th scope="col" abbr="Num Exp" class="top-right">Number of Exposures</th>
  </tr>
  </thead>
  <tfoot>
  <tr>
    <td colspan="4" class="rounded-foot-left"><em>Please check these details carefully; use view/edit tab to change if necessary</em></td>
  <td class="rounded-foot-right">&nbsp;</td>
  </tr>
  </tfoot>
  <tbody>
  <?php foreach ($expArr as $exp) {
  echo "<tr><td>{$exp['type']}</td>\n<td>{$exp['filter']}</td>\n<td>{$exp['binning']}</td>\n<td>{$exp['etime']}</td>\n<td>{$exp['numexp']}</td></tr>";
  } ?>
  </tbody>
  </table>
</div>
</div>
</body>
</html>
