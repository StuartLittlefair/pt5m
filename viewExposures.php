<?php
$queryString = "SELECT * FROM `exposures` WHERE pointingID = " . $_REQUEST['id'];

// query is created. Get matching results from pointing database
require("db.class.php");
// create instance of database class
$db = db_connection();
// get matching exposures
$result = $db->query($queryString);

echo "<p>Viewing exposures for pointing with id # " . $_REQUEST['id'] . "</p>";
$expArr = array();
$result->data_seek(0);
while($row = $result->fetch_array())
  {
    $expArr[] = array(
		      "id"      => $row['id'],
		      "pointID" => $_REQUEST['id'],
		      "type"    => $row['typeFlag'],
		      "filter"  => $row['filter'],
		      "binning" => $row['binning'],
		      "etime"   => $row['exptime'],
		      "numexp"  => $row['numexp']
		      );
  }
  $db->close();
?>
<table id="exposures" cellspacing="0">
<caption>Exposures submitted for this pointing</caption>
<thead>
<tr>
  <th scope="col" abbr="type" class="top-left">Observation Type</th>
  <th scope="col" abbr="filter">Filter</th>
  <th scope="col" abbr="Bin">Binning</th>
  <th scope="col" abbr="Exp Time">Exposure Time</th>
  <th scope="col" abbr="Num Exp">Number of Exposures</th>
  <th scope="col" abbr="Action" class="top-right">Action</th>
</tr>
</thead>
<tfoot>
<tr>
   <td colspan="5" class="rounded-foot-left"><em>Click edit to change an exposures values</em></td>
<td class="rounded-foot-right">&nbsp;</td>
</tr>
</tfoot>
<tbody>
<?php foreach ($expArr as $exp) {
echo "<tr><td>{$exp['type']}</td>\n<td>{$exp['filter']}</td>\n<td>{$exp['binning']}</td>\n<td>{$exp['etime']}</td>\n<td>{$exp['numexp']}</td>";
echo "<td><a href='#' onClick='editExposure(" . $exp['id'] . "," . $exp['pointID'] . "); return false;'>Edit</a>&nbsp;/&nbsp;";
echo "<a href='#' onClick='deleteExposure(" . $exp['id'] . ", " . $exp['pointID'] . "); return false;'>Delete</a></td></tr>";
}
?>
</tbody>
</table>
