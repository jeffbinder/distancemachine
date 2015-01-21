<?php

include '../application.php';

$id = filter_input(INPUT_GET, 'id', FILTER_SANITIZE_STRING, FILTER_FLAG_STRIP_LOW | FILTER_FLAG_NO_ENCODE_QUOTES);

$mysqli = mysqli_connect($mysql_server, $mysql_username, $mysql_passwd)
  or die('Could not connect: ' . $mysqli->connection_error);
mysqli_select_db($mysqli, $main_db_name) or die('Could not select database');

validate_id($id);

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