<?php
  session_start();
  include("subs.php");
?>
<!doctype html>
<html class="no-js" lang="en" dir="ltr">
  <head>
    <meta charset="utf-8">
    <meta http-equiv="x-ua-compatible" content="ie=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>pt5m Queue</title>
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
  if(empty($_SESSION['u_name'])){
  ?>
  <!-- display login form -->
  <form method="post" action="" id="login_form" class="radius" autocomplete="on" novalidate data-abide>
  <div class="row">
  <div class='columns medium-4'>
    <p>
      To submit observations you must
      login. You are also restricted to editing details of pointings that
      belong to you. You may view observations without logging in.
    </p>
  </div>
  <div class='columns medium-8'>
    <label>
      User Name :
      <input name="username" type="text" id="username" value="" placeholder="user name" required  pattern="[\w\s]+"/>
      <span class="form-error" id="objerror">This field must contain Alpha Numeric characters</span>
    </label>

    <label>
      Password:
      <input name="password" type="password" id="password" value="" required/>
    </label>

    <button aria-label="login" class="button radius" value="Login" name="Submit" type="submit">Login</button>

    <div class='panel sucess' id='msgbox' style="display:none" data-closable>
      <p>Logging in</p>
      <button class="close-button" aria-label="Dismiss alert" type="button" data-close>
        <span aria-hidden="true">&times;</span>
      </button>
    </div>
  </div>
  </div>
  </form>

  <?php
  }
  else
  {
  //if logout then destroy the session and redirect the user
  if(isset($_GET['logout']))
  {
    session_destroy();
    //header("Location:index.php");
    echo '<META HTTP-EQUIV="Refresh" Content="0; URL=index.php">';
    exit;
  }

  // otherwise we are logged in and we should display logout page
  $uid = $_SESSION['u_name'];
  // connect to DB
  require("db.local.class.php");
  // create instance of database class
  $db = new mysqldb();
  $db->select_db();
  $result = $db->query("SELECT fullName from `tbl_user` WHERE user_name = '" .$uid . "'");
  $row = $db->fetch_array($result);
  $fullName = $row['fullName'];

  echo '<div class="row">';
  echo "<div class='columns medium-10'>";
  echo "<p>Welcome to the pt5m queue tool, " . $fullName . "</p>";
  echo "</div>";
  echo "<div class='columns medium-2'>";
  echo "<a class='button' href='index.php?logout'><b>Logout<b></a>";
  echo "</div></div>";
  }
  ?>
  </div>
  <script src="js/vendor/jquery.js"></script>
  <script src="js/vendor/what-input.js"></script>
  <script src="js/vendor/foundation.js"></script>
  <script src="js/app.js"></script>
      <script language="javascript">
    //  Developed by Roshan Bhattarai
    //  Visit http://roshanbh.com.np for this script and more.
    //  This notice MUST stay intact for legal use

    $(document).ready(function()
    {
      $("#login_form").submit(function()
      {
        //remove all the class add the messagebox classes and start fading
        $('#msgbox').find('p').text('Validating....');
        $("#msgbox").removeClass().addClass('warning callout').fadeIn(1000);

        //check the username exists or not from ajax
        $.post("ajax_login.php",{ user_name:$('#username').val(),password:$('#password').val(),rand:Math.random() }, function(data){
          if(data=='yes') { //if correct login detail
            $("#msgbox").fadeTo(200,0.1,function(){  //start fading the message
              //add message and change the class of the box and start fading
              $(this).find('p').text('Logging in.....');
              $(this).removeClass().addClass('success callout').fadeTo(900,1,function(){
                //redirect to secure page
                document.location='index.php';
              });
            });
          }else{
            $("#msgbox").fadeTo(200,0.1,function(){ //start fading the message
              //add message and change the class of the box and start fading
              $(this).find('p').text('Your login details are incorrect')
              $(this).removeClass().addClass('alert callout').fadeTo(900,1);
            });
          }
        });
        return false; //not to post the form physically
      });
      //now call the ajax also focus move from
      $("#password").blur(function(){
        $("#login_form").trigger('submit');
      });
    });
    </script>
  </body>
</html>
