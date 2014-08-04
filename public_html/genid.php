<?php

include 'application.php';

$mysqli = mysqli_connect($mysql_server, $mysql_username, $mysql_passwd)
  or die('Could not connect: ' . $mysqli->connect_error);
mysqli_select_db($mysqli, $main_db_name) or die('Could not select database');

$text = filter_input(INPUT_POST, 'text', FILTER_SANITIZE_STRING, FILTER_FLAG_STRIP_LOW);

validate_text($text);

$id = generate_id($text);

mysqli_close($mysqli);

echo $id;

?>
