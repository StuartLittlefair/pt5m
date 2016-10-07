<?php session_start(); ?>
<?php
include('subs.php');

// check we're logged in
if(empty($_SESSION['u_name'])) {
  send_error("Cannot edit pointings if not logged in");
}

if(isset($_REQUEST['formSubmit']))
{
//process the edit
$queryString = "UPDATE `exposures` SET ";
$queryString = $queryString . "typeFlag = '{$_REQUEST['type']}', ";
$queryString = $queryString . "filter = '{$_REQUEST['filter']}', ";
$queryString = $queryString . "binning = '{$_REQUEST['binning']}', ";
$queryString = $queryString . "exptime = '{$_REQUEST['exptime']}', ";
$queryString = $queryString . "numexp = '{$_REQUEST['numexp']}' ";
$queryString = $queryString . " WHERE id = " . $_REQUEST['id'];

// query is created. update exposure
require("db.local.class.php");
// create instance of database class
$db = new mysqldb();
$db->select_db();
// update exposure
$result = $db->query($queryString);

// also need to reset pointing associated with this exposure as well
// UPDATE `pointings` SET status='defined' WHERE pointingID = (SELECT pointingID from `exposures` WHERE  id=ID);
$queryString = "UPDATE `pointings` SET status='defined' WHERE pointingID = (SELECT pointingID from `exposures` WHERE id = "  . $_REQUEST['id'] . ");";
$result = $db->query($queryString);
echo "OK";
}
else
{

// the pointing ID is submitted to this script, as is the exposure ID
$id = $_REQUEST['id'];
$pointID = $_REQUEST['pointID'];

// query is created. Get matching results from pointing database
require("db.local.class.php");

// create instance of database class
$db = new mysqldb();
$db->select_db();

// get user for this pointing
$result = $db->query("SELECT userID from `pointings` WHERE pointingID = " . $_REQUEST['pointID']);
$row = $db->fetch_array($result);
$uid = $row[0];
$match = strcmp($uid,$_SESSION['u_name']);

if(strcmp($uid,$_SESSION['u_name']) !=0 ){
   send_error("This pointing belongs to " . $uid);
}

$queryString = "SELECT * FROM `exposures` WHERE id = " . $id;
$result = $db->query($queryString);

// should be no more than one result. if there is, we've got trouble
$numRes = $db->num_rows($result);
if($numRes>1) die("Error: more than one exposure is associated with this ID. Contact Queue administrator");
$result = $db->fetch_array($result);
$db->kill();
?>

<form data-abide class='radius' id="editExpform" action="" method="" novalidate>
<?php echo "<h3>pt5m exposure edit Form for exposure #" . $id . "</h3>";?>

<input type="hidden" name="id" <?php echo "value='" . $id . "'"; ?> />
<input type="hidden" id="pointID" name="pointID" <?php echo "value='" . $pointID . "'"; ?> />
<input type="hidden" name="formSubmit"/>

<div class='row'><div class='columns small-6'>
<label>Type:</label>
<select name="type" required>
<?php
$options = array
  (
   'SCIENCE' => 'SCIENCE',
   'FOCUS' => 'FOCUS',
   'DARK' => 'DARK',
   'BIAS' => 'BIAS'
   );
$toSelect = $result['typeFlag'];
foreach($options as $key => $value)
{
  print "<option";
  if($key == $toSelect)
    {
      print " selected ";
    }
  print ">" . $value . "</option>";
}
?>
</select>

<label>Filter:</label>
<select name="filter" required>
<?php
$options = array
  (
   'H' => 'H',
   'B' => 'B',
   'V' => 'V',
   'R' => 'R',
   'I' => 'I',
   '-' => '-'
   );
$toSelect = $result['filter'];
foreach($options as $key => $value)
{
  print "<option";
  if($key == $toSelect)
    {
      print " selected ";
    }
  print ">" . $value . "</option>";
}
?>
</select>

<label>Binning:</label>
<input type="text" name="binning" maxlength="5" <?php echo "value='" . $result['binning'] . "'" ?> placeholder="2" required pattern='number'>

</div><div class='columns small-6'>

<label>Exposure Time:</label>
<input type="text" name="exptime" maxlength="5" <?php echo "value='" . $result['exptime'] . "'" ?> placeholder="" required pattern='number'>

<label>Num Exposures:</label>
<input type="text" name="numexp" maxlength="5" <?php echo "value='" . $result['numexp'] . "'" ?> placeholder="" required pattern='number'>

<button aria-label="submit form" class="button radius" value="Submit" type="submit">Submit</button>
</form>

<script type="text/javascript">
  // on submission, we want the form to submit, but not to redirect.
  // I'm doing this by capturing the form submission event and posting it via ajax
  // on page load
  // wait for the DOM to be loaded
  // capture form submission using AJAX.
  // on form submission, clear form div and place results in pointing table div
  $(document).ready(function() {
      $('#editExpform').submit(function(event){

	      var options = {
          url:  'editExposure.php',
          data: $('#editExpform').serialize(),
          success: function(data){
            $('#editModal').html(data);
            $('#editModal').append(`<button class="close-button" data-close aria-label="Close modal" type="button">
				                            <span aria-hidden="true">&times;</span>
                                    </button>`);
            // open modal
            $('#editModal').foundation('open');
            // re-init foundation plugin
            $(document).foundation();
          } // if it's OK
        };
        //process the form
        $.ajax(options);

        console.log($('#pointID').val());
        respawnNoExp();
        viewExposures($('#pointID').val());

        // stop the form from submitting the normal way
        event.preventDefault(); //STOP default action
    });
  });
</script>
<!-- matches if/else block -->
<?php } ?>


