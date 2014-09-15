<?php

include 'application.php';

$mysqli = mysqli_connect($mysql_server, $mysql_username, $mysql_passwd)
  or die('Could not connect: ' . $mysqli->connect_error);
mysqli_set_charset($mysqli, 'utf8');
mysqli_select_db($mysqli, $main_db_name) or die('Could not select database');

// Preload the total word counts, since they may be used multiple times.
$totals = [];
foreach ($corpora as $corpus) {
    $totals[$corpus] = get_totals($corpus);
}
echo json_encode($totals);

mysqli_close($mysqli);

?>
