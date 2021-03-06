<?php

header('Content-Type: application/json');

include 'application.php';

$mysqli = mysqli_connect($mysql_server, $mysql_username, $mysql_passwd)
  or die('Could not connect: ' . $mysqli->connect_error);
mysqli_set_charset($mysqli, 'utf8');
mysqli_select_db($mysqli, $main_db_name) or die('Could not select database');

$id = filter_input(INPUT_POST, 'id', FILTER_SANITIZE_STRING, FILTER_FLAG_STRIP_LOW);
$text = filter_input(INPUT_POST, 'text', FILTER_UNSAFE_RAW);
$title = filter_input(INPUT_POST, 'title', FILTER_UNSAFE_RAW, FILTER_FLAG_STRIP_LOW);
$corpus = filter_input(INPUT_POST, 'corpus', FILTER_SANITIZE_STRING, FILTER_FLAG_STRIP_LOW);

validate_id($id);
validate_text($text);
validate_title($title);
validate_corpus($corpus);

set_time_limit(300);

gen_annotated_text($id, $text, $title, $corpus, false, false, 0);

mysqli_close($mysqli);

echo json_encode(true);

?>
