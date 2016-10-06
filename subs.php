<?php

function send_error($errorString){
  echo "<h3>ERROR: $errorString</h3>";
  exit();
}

function send_error_retry($errorString,$url){
  echo "<h3>ERROR: $errorString</h3>";
  echo "<p>click <a href='" . $url . "'>Here</a> to try again</p>";
  exit();
}

function raString($angle)
{
  // converts decimal degrees into a printable RA string
  $decHours = $angle*24.0/360.0;
  $hours = floor($decHours);
  $mins  = floor(($decHours-$hours)*60.0);
  $secs  = 60.0*(($decHours-$hours)*60.0 - $mins);
  return sprintf("%02d:%02d:%04.1f",$hours,$mins,$secs);
}

function decString($angle)
{
  // converts decimal degrees into a printable DEC string
  $sign = "+";
  if($angle < 0.0)
    {
      $angle = $angle*-1.0;
      $sign = "-";
    }
  $deg = floor($angle);
  $mins  = floor(($angle-$deg)*60.0);
  $secs  = 60.0*(($angle-$deg)*60.0 - $mins);
  return sprintf("%s%02d:%02d:%04.1f",$sign,$deg,$mins,$secs);
}

function degrees($str,$format){
  // converts position string of type $format into decimal degrees
  if($format == "hours")
    {
      $pattern = '/^(\d\d)[:\s](\d\d)[:\s](\d\d(\.\d*)*)$/';
      if( preg_match($pattern,$str,$matches) )
	{
	  $hours = $matches[1];
	  $mins  = $matches[2];
	  $secs  = $matches[3];
	  $hours = $hours + $mins/60.0 + $secs/3600.0;
	  return $hours*360.0/24.0;
	} 
      else 
	{
	  send_error("Couldn't match RA string");
	}  
    }
  elseif($format == "degrees")
    {
      $pattern = '/^([-\+])(\d\d)[:\s](\d\d)[:\s](\d\d(\.\d*)*)$/';
      if(preg_match($pattern,$str,$matches))
	{
	  $sign  = $matches[1];
	  $deg   = $matches[2];
	  $mins  = $matches[3];
	  $secs  = $matches[4];
	  if ($sign == "-") 
	    {
	      $deg = $deg*-1.0;
	      $deg = $deg - $mins/60.0 - $secs/3600.0;
	    }
	  else
	    {
	      $deg = $deg +$mins/60.0 + $secs/3600.0;
	    }
	  return $deg;
	}
      else
	{
	  send_error("Couldn't match Dec string");
	}
    }	  
  else
    {
      send_error("position string format not recognised");
    }
		 
}

?>
