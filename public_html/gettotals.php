<?php

include 'application.php';

$mysqli = mysqli_connect($mysql_server, $mysql_username, $mysql_passwd)
  or die('Could not connect: ' . $mysqli->connect_error);
mysqli_select_db($mysqli, $main_db_name) or die('Could not select database');

// Preload the total word counts, since they may be used multiple times.
$totals = [];
foreach ($regions as $region) {
    $totals[$region] = get_totals($region);
}
echo json_encode($totals);

mysqli_close($mysqli);

?>
