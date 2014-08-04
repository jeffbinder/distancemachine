<?php

header('Content-Type: application/json');

include 'application.php';

$mysqli = mysqli_connect($mysql_server, $mysql_username, $mysql_passwd)
  or die('Could not connect: ' . $mysqli->connect_error);
mysqli_select_db($mysqli, $main_db_name) or die('Could not select database');

$id = filter_input(INPUT_POST, 'id', FILTER_SANITIZE_STRING, FILTER_FLAG_STRIP_LOW);
$text = filter_input(INPUT_POST, 'text', FILTER_UNSAFE_RAW);
$title = filter_input(INPUT_POST, 'title', FILTER_UNSAFE_RAW, FILTER_FLAG_STRIP_LOW);
$region = filter_input(INPUT_POST, 'region', FILTER_SANITIZE_STRING, FILTER_FLAG_STRIP_LOW);

validate_id($id);
validate_text($text);
validate_title($title);
validate_region($region);

gen_annotated_text($id, $text, $title, $region);

mysqli_close($mysqli);

echo json_encode(true);

?>
