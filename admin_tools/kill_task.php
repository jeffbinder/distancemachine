<?php

include 'connection.php';

if (count($argv) != 2) {
    die("Specify the ID of the task to kill.");
}
$id = $argv[1];

$mysqli = mysqli_connect($mysql_server, $mysql_username, $mysql_passwd)
  or die('Could not connect: ' . $mysqli->connection_error);
mysqli_select_db($mysqli, $main_db_name) or die('Could not select database');

$query = $mysqli->prepare("
UPDATE task
SET status = 'killed'
WHERE id = ?
");
$query->bind_param('s', $id);

$query->execute() or die('Query failed: ' . $mysqli->error);

mysqli_close($mysqli);

echo "Task killed.\n";

?>