<?php session_start(); ?>
<?php
include('subs.php');

// check we're logged in
if(empty($_SESSION['u_name'])) {
  send_error("Cannot edit pointings if not logged in");
}

if(!isset($_REQUEST['editSubmit']) && !isset($_REQUEST['id'])) {
  send_error("invalid call to editPointing.php");
}

if(isset($_REQUEST['editSubmit']))
{

// connect to DB
require("db.local.class.php");
// create instance of database class
$db = new mysqldb();
$db->select_db();

// process the edit
// make SQL dateTime from Date and Time entries
$startUT = $_REQUEST['startUTC'];
$endUT = $_REQUEST['endUTC'];

// check it's valid
$format = 'd-m-Y H:i';
$begin = new DateTime($startUT,new DateTimeZone('UTC'));
$end   = new DateTime($endUT,new DateTimeZone('UTC'));
if($end < $begin){
  send_error_retry("Invalid submission: end date before start date", "submit.html");
  exit();
}

// sanitize possible ra, dec formats
$ra  = degrees($_REQUEST['ra'], "hours");
$dec = degrees($_REQUEST['dec'], "degrees");

// sanitise moon brightness
$moon = "B";
if($_REQUEST['maxMoon'] == "Dark"){
  $moon = "D";
} elseif($_REQUEST['maxMoon'] == "Grey"){
  $moon = "G";
}

// parse pier flip
$flip = 0;
if($_POST['flip'] == "on"){
  $flip = 1;
}

// parse time critical flag
$ToO = 0;
if($_REQUEST['ToO'] == "on"){
  $ToO = 1;
}

// parse guiding flag
$guide = 0;
if($_REQUEST['guide'] == "on"){
  $guide = 1;
}

$queryString = "UPDATE `pointings` SET ";
$queryString = $queryString . "object = '{$_REQUEST['object']}', ";
$queryString = $queryString . "RA = '$ra', ";
$queryString = $queryString . "decl = '$dec', ";
$queryString = $queryString . "priority = '{$_REQUEST['priority']}', ";
$queryString = $queryString . "minAlt = '{$_REQUEST['minAlt']}', ";
$queryString = $queryString . "minTime = '{$_REQUEST['minTime']}', ";
$queryString = $queryString . "maxMoon = '$moon', ";
$queryString = $queryString . "userID = '{$_REQUEST['id']}', ";
$queryString = $queryString . "startUTC = '$startUT', ";
$queryString = $queryString . "stopUTC = '$endUT', ";
$queryString = $queryString . "pierFlip = '$flip', ";
$queryString = $queryString . "ToO = '$ToO', ";
$queryString = $queryString . "guide = '$guide', ";
$queryString = $queryString . "status = 'defined' ";
$queryString = $queryString . " WHERE pointingID = " . $_REQUEST['pointingID'];
// update pointing
$result = $db->query($queryString);
$db->kill();
echo "<h3>Pointing updated</h3>";

}else{
// the pointing ID is submitted to this script, as is the original query to the queue
$id = $_REQUEST['id'];

// query is created. Get matching results from pointing database
require("db.local.class.php");

// create instance of database class
$db = new mysqldb();
$db->select_db();

$queryString = "SELECT * FROM `pointings` WHERE pointingID = " . $id;


$result = $db->query($queryString);
// should be no more than one result. if there is, we've got trouble
$numRes = $db->num_rows($result);

if($numRes > 1) die("Error: more than one pointing is associated with this ID. Contact Queue administrator");
$result = $db->fetch_array($result);
$db->kill();


// get user for this pointing
$uid = $result['userID'];
$match = strcmp($uid,$_SESSION['u_name']);

if(strcmp($uid,$_SESSION['u_name']) !=0 ){
   send_error("This pointing belongs to " . $uid);
}
?>

<form data-abide id="editform" class="radius" action="" method="" novalidate>
<?php echo "<h3>pt5m pointing edit Form for pointing #" . $id . "</h3>";?>


<?php
$status = $result['status'];
echo "<input type='hidden' name='status' value='" . $status . "'/>";
echo "<input type='hidden' name='pointingID' value='" . $id . "'/>";
echo "<input type='hidden' name='id' value='" . $result['userID'] . "'/>";
echo "<input type='hidden' name='editSubmit'/>"
?>

<div class='row'>
    <div class='columns small-12 medium-6'>
      <label>Object Name:
      <input type="text" name="object" spellcheck="true" placeholder="Object Name" required  pattern="[\w\s]+" <?php echo "value='" . $result['object'] . "'" ?>/>
      <span class="form-error" id="objerror">This field must contain Alpha Numeric characters</span>
      </label>

     <label>Right Ascension:
     <?php
        $ra  = raString($result['RA']);
     ?>
     <input type="text" name="ra" spellcheck="true" placeholder="hh:mm:ss"required  pattern="\d\d[:\s]\d\d[:\s]\d\d(\.\d*)*" data-validator="ra" <?php echo "value='" . $ra . "'" ?>/>
     <span class="form-error" id="raerror">This field must contain a valid ra</span>
     </label>

     <label>Declination:
     <?php
       $dec = decString($result['decl']);
     ?>
     <input type="text" name="dec" spellcheck="true" placeholder="+dd:mm:ss.s"required  pattern="[-\+]\d\d[:\s]\d\d[:\s]\d\d(\.\d*)*" <?php echo "value='" . $dec . "'" ?>/>
     <span class="form-error" id="decerror">This field must contain a vaild dec</span>
     </label>

    <label>Priority:
    <select name="priority" required>
    <?php
    $options = array
      (
      0 => '0',
      1 => '1',
      2 => '2',
      3 => '3',
      4 => '4',
      5 => '5'
      );
    $toSelect = $result['priority'];
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
    <span class="form-error">You must select a priority</span>
    </label>

    <label id="minAltLabel">Minimum Altitude:</label>
    <div class="row">
    <div class='columns small-9'>
        <div class="slider" aria-labelledby="minAltLabel" data-slider data-initial-start=<?php echo "'" . $result['minAlt'] . "'" ?> data-end='90'>
            <span class="slider-handle"  data-slider-handle role="slider" tabindex="1" aria-controls="sliderOutput2"></span>
            <span class="slider-fill" data-slider-fill></span>
        </div>
    </div>
    <div class='columns small-3'>
        <input type="number" name="minAlt" id="sliderOutput2">
    </div>
    </div>
    <button aria-label="submit form" class="button radius" value="Submit" name="formSubmit" type="submit">Submit</button>

  </div> <!-- end of first column -->
  <div class='columns small-12  medium-6'>

    <label>Minimum Observing Time:
    <input type="text" name="minTime" spellcheck="true" placeholder="time in secs" required  pattern="number" <?php echo "value='" . $result['minTime'] . "'" ?>/>
    <span class="form-error" id="minTimeError">This field must contain a number</span>
    </label>

    <label>Moon Constraints:
    <select name="maxMoon" required>
      <?php
      $options = array
        (
        'Dark' => 'Dark',
        'Grey' => 'Grey',
        'Bright' => 'Bright'
        );
      if( $result['maxMoon'] == 'D')
        {
          $toSelect = "Dark";
        }
      else if ($result['maxMoon'] == 'G')
        {
          $toSelect = "Grey";
        }
      else if ($result['maxMoon'] == 'B')
        {
          $toSelect = "Bright";
      }
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
    <span class="form-error" id="maxMoonError">You must select a value from the dropdown</span>
    </label>

    <?php
    date_default_timezone_set("GMT");
    $start = new DateTime($result['startUTC']);
    $startDate = $start->format("Y-m-d");
    $startTime = $start->format("H:i");
    $stop = new DateTime($result['stopUTC']);
    $stopDate = $stop->format("Y-m-d");
    $stopTime = $stop->format("H:i");
    ?>
    <label>start Date (UT):
    <input type="text" id="startUTC" name="startUTC" data-date-format="yyyy-mm-dd hh:ii" data-date-language="en" required <?php echo "value='".$start->format("Y-m-d H:i")."'"?> >
    <span class="form-error" id="startDateError">You must enter a date.</span>
    </label>

    <label>end Date (UT):
    <input type="text" id="endUTC" name="endUTC" data-date-format="yyyy-mm-dd hh:ii" data-date-language="en" data-validator="greater_than" data-greater-than="startUTC" required <?php echo "value='".$stop->format("Y-m-d H:i")."'"?>>
    <span class="form-error" id="endUTCError">You must enter an end date (after start date).</span>
    </label>

    <div class="row">
    <div class='column small-4'>
      <p>Pier Flip:</p>
      <div class="switch radius small">
          <input class="switch-input" id="flip" type="checkbox" name="flip" <?php if ($result['flip']){echo "checked";}?>>
          <label class="switch-paddle" for="flip">
              <span class="show-for-sr">Allow Pier Flip:</span>
              <span class="switch-active" aria-hidden="true">Yes</span>
              <span class="switch-inactive" aria-hidden="true">No</span>
          </label>
      </div>
    </div>

    <div class='column small-4'>
      <p>ToO:</p>
      <div class="switch radius small">
          <input class="switch-input" id="ToO" type="checkbox" name="ToO" <?php if ($result['ToO']){echo "checked";}?> />
          <label class="switch-paddle" for="ToO">
              <span class="show-for-sr">Time Critical:</span>
              <span class="switch-active" aria-hidden="true">Yes</span>
              <span class="switch-inactive" aria-hidden="true">No</span>
          </label>
      </div>
    </div>

    <div class='column small-4'>
      <p>AutoGuide:</p>
      <div class="switch radius small">
          <input class="switch-input" id="guide" type="checkbox" name="guide" <?php if ($result['guide']){echo "checked";}?>/>
          <label class="switch-paddle" for="guide">
              <span class="show-for-sr">Autoguiding:</span>
              <span class="switch-active" aria-hidden="true">Yes</span>
              <span class="switch-inactive" aria-hidden="true">No</span>
          </label>
      </div>
    </div>
    </div> <!-- end switch row -->

  </div>
  </div> <!-- end form row -->
</form>


<script src="https://cdnjs.cloudflare.com/ajax/libs/foundation-datepicker/1.5.0/js/foundation-datepicker.min.js"></script>
<script type="text/javascript">
// add date pickers
$(document).ready(function(){
    $('#startUTC').fdatepicker({
    format: 'yyyy-mm-dd hh:ii',
    disableDblClickSelection: true,
    pickTime: true
    });

    $('#endUTC').fdatepicker({
    format: 'yyyy-mm-dd hh:ii',
    disableDblClickSelection: true,
    pickTime: true
    });
});

// add RA validator
Foundation.Abide.defaults.validators['ra'] = function($el, required, parent){
    var value = $el.val();
    value = value.replace(/\s/g,":");
    var raSplit = value.split(":");
    var mins = parseFloat(raSplit[1]);
    var secs = parseFloat(raSplit[2]);
    var RA = parseFloat(raSplit[0]) + parseFloat(raSplit[1])/60.0 + parseFloat(raSplit[2])/3600.0;
    return ((RA >= 0.0) && (RA < 24.0) && (mins < 60.0) && (secs < 60.0));
};


// add DEC validator
Foundation.Abide.defaults.validators['dec'] = function($el, required, parent){
    var value = $el.val();
    value = value.replace(/\s/g,":");
    var decSplit = value.split(":");
    var dec = parseFloat(decSplit[0]) + parseFloat(decSplit[1])/60.0 + parseFloat(decSplit[2])/3600.0;
    var mins = parseFloat(decSplit[1]);
    var secs = parseFloat(decSplit[2]);
    return ((dec >=-90.0) && (dec <= 90.0) && (mins < 60.0) && (secs < 60.0));
};

// Enforce END > START
Foundation.Abide.defaults.validators['greater_than'] = function($el, required, parent) {
  // parameter 1 is jQuery selector
  if (!required) return true;
  var from = $('#'+$el.attr('data-greater-than')).val(), to = $el.val();
  var fromDT = new Date(from.replace(/\s/g, 'T'));
  var toDT = new Date(to.replace(/\s/g, 'T'));
  return (toDT > fromDT);
};

</script>

<script type="text/javascript">
  // on submission, we want the form to submit, but not to redirect.
  // I'm doing this by capturing the form submission event and posting it via ajax
  // on page load
  // wait for the DOM to be loaded
  // capture form submission using AJAX.
  // on form submission, clear form div and place results in pointing table div
  $(document).ready(function() {
      $('#editform').submit(function(event){

	      var options = {
          url:  'editPointing.php',
          data: $('#editform').serialize(),
          success: function(data){
            console.log(data);
            $('#editModal').html(data);
            $('#editModal').append(`<button class="close-button" data-close aria-label="Close modal" type="button">
				                            <span aria-hidden="true">&times;</span>
                                    </button>`);
            // open modal
            modal.foundation('open');
            // re-init foundation plugin
            $(document).foundation();
          } // if it's OK
        };
        //process the form
        $.ajax(options);

        respawnNoExp();

        // stop the form from submitting the normal way
        event.preventDefault(); //STOP default action
    });
  });
</script>

<!-- matches if/else block -->
<?php } ?>

