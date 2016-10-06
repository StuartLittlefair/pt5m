<?php

  // this stuff is to actually process a form submission
include('subs.php');

// start SQL query. We'll append stuff on it as needed
//$queryString = "SELECT * FROM `pointings` WHERE (status!='deleted')";
$queryString = "SELECT * FROM `pointings` WHERE (1)";

// validate form input
$validForm = True;

// has the user put any object information in?
$checkByObject = False;
$boxSearch    = False;


if( (!empty($_REQUEST['object'])) || (!empty($_REQUEST['ra'])) ||
    (!empty($_REQUEST['dec'])) )
  {
    $checkByObject = True;
  }

// if the user has given us positional information, has he specified enough?
if($checkByObject && empty($_REQUEST['object']))
  {
    // we are definitely checking on ra &dec
    if(empty($_REQUEST['ra']) || empty($_REQUEST['dec'])|| empty($_REQUEST['box']) )
      {
	// but we don't have enough info to do so!
	$validForm = False;
	$errorString = "must supply an RA and Dec if doing a position search";
	send_error_retry($errorString,"view.html");
      }
    else
      {
	$boxSearch = True;
      }
  }

if($boxSearch)
  {
    $raVal = degrees($_REQUEST['ra'],"hours");
    if( ($raVal < 0) || ($raVal > 24.0)) {
      send_error_retry("Invalid RA","view.html");
    }
    $decVal = degrees($_REQUEST['dec'],"degrees");
    if( ($raVal < 0) || ($raVal > 24.0)) {
      send_error_retry("Invalid RA","view.html");
    }
    if($_REQUEST['box'] < 0){
      send_error_retry("Box size must be greater than 0","view.html");
    }
    if($_REQUEST['box'] > 360){
      send_error_retry("Cannot search boxes larger than 6 degrees","view.html");
    }
    $raLow = $raVal - $_REQUEST['box']/60.0/2.0;
    $raHigh = $raVal + $_REQUEST['box']/60.0/2.0;
    $decLow = $decVal - $_REQUEST['box']/60.0/2.0;
    $decHigh = $decVal + $_REQUEST['box']/60.0/2.0;

    $queryString = $queryString . " (RA BETWEEN $raLow AND $raHigh)";
    $queryString =  $queryString . " (decl BETWEEN $decLow AND $decHigh)";
  }
elseif($checkByObject && !$boxsearch)
{
  $obj = $_REQUEST['object'];
  // checking by object, but not on RA/Dec, so search on object name
  $queryString = $queryString . " (object='$obj')";
}

// is form empty? Currently return whole database if so
$emptyForm = True;

// remove positional entries from form and see if others are empty (being careful with priority)
unset($_REQUEST['ra']);
unset($_REQUEST['dec']);
unset($_REQUEST['box']);
unset($_REQUEST['object']);
unset($_REQUEST['submitForm']);
if ($checkByObject) $emptyForm = False;

// see if form is empty (being careful with priority, start and end dates, which are always full of something!)
$validEntries = array('pID','priority','status','startDate','endDate','id');
$filledEntries = array_intersect($validEntries,array_keys($_REQUEST));

if (count($filledEntries) == 4) $emptyForm = True;
foreach($_REQUEST as $key => $val)
{
  if(($key == "priority") && ($val != "Any"))
	$emptyForm = False;
  if(($key == "startDate") && ($val != ""))
	$emptyForm = False;
  if(($key == "endDate") && ($val != ""))
	$emptyForm = False;
  if(($key == "id") && ($val != ""))
	$emptyForm = False;
}

//if($emptyForm) $queryString = $queryString . " 1";

// add extra fields
if(!empty($_REQUEST['id']))
  {
    $user = $_REQUEST['id'];
    $queryString = $queryString . " (userID='$user')";
  }
if(!empty($_REQUEST['priority']) || ($_REQUEST['priority'] == "0"))
  {
    $priority = $_REQUEST['priority'];
    if($priority != "Any")
      {
	$queryString = $queryString . " (priority='$priority')";
      }
  }

if(!empty($_REQUEST['status']))
  {
    $status = $_REQUEST['status'];
    if($status != "Any")
      {
	$queryString = $queryString . " (status='$status')";
      }
  }
// dates now. User can supply
// 1) both dates (all runs with start Date later than supplied date selected)
// 2) start date and no end date (all runs which start later than startDate selected)
// 3) end date and no start date (today is used as start date)

if(!empty($_REQUEST['startDate']) && !empty($_REQUEST['endDate']))
  {
    // both dates supplied.
    $startUTC = $_REQUEST['startDate'];
    $stopUTC  = $_REQUEST['endDate'];
    $queryString = $queryString .
	     " (startUTC BETWEEN str_to_date('" . $startUTC . "', '%Y-%m-%d') AND str_to_date('" . $stopUTC . "', '%Y-%m-%d') )";
  }
else if(empty($_REQUEST['startDate']) && !empty($_REQUEST['endDate']))
  {
    // only end date given, use today as startUTC
    $startUTC = date('Y-m-d');
    $stopUTC  = $_REQUEST['endDate'];
    $queryString = $queryString .
	     " (startUTC BETWEEN str_to_date('" . $startUTC . "', '%Y-%m-%d') AND str_to_date('" . $stopUTC . "', '%Y-%m-%d') )";
  }
else if(!empty($_REQUEST['startDate']) && empty($_REQUEST['endDate']))
  {
    // only start date given, all runs later than start date
    $startUTC = $_REQUEST['startDate'];
    $queryString = $queryString .
	     " (startUTC > to_date('" . $startUTC . "', '%Y-%m-%d') )";
  }

// put joining AND in string where needed
$queryString = str_replace(") (",") AND (",$queryString);

// append order by
$queryString = $queryString . " ORDER BY pointingID DESC";

// finally a special case. IF pID is set, then query by pointing and nothing else
if(isset($_REQUEST['pID'])){
  $queryString = "SELECT * FROM `pointings` WHERE (pointingID = " . $_REQUEST['pID'] .");";
 }



// create instance of database class
try{
  // query is created. Get matching results from pointing database
  require("db.local.class.php");
  $db = new mysqldb();
  $db->select_db();

  $result = $db->query($queryString);
  $numRes = $db->num_rows($result);
}catch (Exception $e) {
  send_error_retry("SQL query failed", "view.html");
}

if($numRes == 0){
  send_error_retry("No Results Found", "view.html");
}

$entries = array();

date_default_timezone_set("GMT");
while($row = $db->fetch_array($result))
  {
    $pointingID = $row[0];
    $object     = $row[1];
    $ra         = raString($row[2]);
    $decl       = decString($row[3]);
    $priority   = $row[4];
    $minAlt     = $row[5];
    $minTime    = $row[6];
    $maxMoon    = $row[7];
    $userID     = $row[8];
    $startUT    = new DateTime($row[9]);
    $stopUT     = new DateTime($row[10]);
    $too        = $row[11];
    $guide      = $row[14];
    $status     = $row[13];
    $entries[] = array(
		       "id" => $pointingID,
		       "object" => $object,
		       "ra" => $ra,
		       "dec" => $decl,
		       "pri" => $priority,
		       "ToO" => $too,
		       "guide" => $guide,
		       "minAlt" => $minAlt,
		       "minTime" => $minTime,
		       "moon" => $maxMoon,
		       "user" => $userID,
		       "startUT" => $startUT,
		       "stopUT" => $stopUT,
		       "status" => $status);
  }
  $db->kill();
?>

<?php
$pointString = "pointing";
$matchString = "match";
if($numRes > 1) $pointString = $pointString . "s";
if($numRes == 1) $matchString = "matches";
?>
<?php echo "<h3>Found $numRes $pointString which $matchString criteria</h3>"; ?>

<?php echo "<p>Click <a href='view.html'>here</a> to submit another query</p>"; ?>

<div class="table-scroll">
<table class="hover" id="pointing" cellspacing="0">
<caption>Pointings</caption>
<thead>
   <tr>
   <th scope="col" abbr="Object" class="top-left">Object</th>
   <th scope="col" abbr="ID">ID</th>
   <th scope="col" abbr="RA">RA (J2000)</th>
   <th scope="col" abbr="Dec">Dec (J2000)</th>
   <th scope="col" abbr="Status">Status</th>
   <th scope="col" abbr="Priority">Pri</th>
   <th scope="col" abbr="Time critical?">TC</th>
   <th scope="col" abbr="Autoguiding?">Guide</th>
   <th scope="col" abbr="Min Alt">Min. Alt. (deg)</th>
   <th scope="col" abbr="Min Time">Min. Time (sec)</th>
   <th scope="col" abbr="Moon">Moon</th>
   <th scope="col" abbr="User">User ID</th>
   <th scope="col" abbr="Start">Start UTC</th>
   <th scope="col" abbr="Stop">Stop UTC</th>
   <th scope="col" abbr="Action" class="top-right">Action</th>
   </tr>
</thead>
<tfoot>
<tr>
   <td colspan="14" class="rounded-foot-left"><em>Click 'View' to see associated observations, click 'Edit' to change a pointing</em></td>
<td class="rounded-foot-right">&nbsp;</td>
</tr>
</tfoot>

<tbody>

<?php
foreach($entries as $entry)
{
  $startUT = $entry['startUT'];
  $startDate = $startUT->format('Y-m-d');
  $stopUT = $entry['stopUT'];
  $stopDate = $stopUT->format('Y-m-d');
  $pointID = $entry['id'];
  echo "<tr>";
  echo "<td>{$entry['object']}</td>";
  echo "<td>$pointID</td>";
  echo "<td>{$entry['ra']}</td>";
  echo "<td>{$entry['dec']}</td>";
  echo "<td>{$entry['status']}</td>";
  echo "<td>{$entry['pri']}</td>";
  if($entry['ToO'] == 1){
	echo "<td>yes</td>";
  }else{
	echo "<td>no</td>";
  }
  if($entry['guide'] == 1){
	echo "<td>yes</td>";
  }else{
	echo "<td>no</td>";
  }
  echo "<td>{$entry['minAlt']}</td>";
  echo "<td>{$entry['minTime']}</td>";
  echo "<td>{$entry['moon']}</td>";
  echo "<td>{$entry['user']}</td>";
  echo "<td>$startDate</td>";
  echo "<td>$stopDate</td>";
  echo "<td><a href='#' onClick='viewExposures(" . $pointID . "); return false;'>View</a>&nbsp;/&nbsp;";
  echo "<a href='#' onClick='editPointing(" . $pointID . "); return false;'>Edit</a>&nbsp;/&nbsp;";
  echo "<a href='#' onClick='deletePointing(" . $pointID . "); return false;'>Delete</a></td>";
  echo "</tr>";
 }
?>



