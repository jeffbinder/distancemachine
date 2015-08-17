<?php

include 'application.php';

header('Content-Type: application/json');

check_archive_mode();

$word = filter_input(INPUT_GET, 'word', FILTER_SANITIZE_STRING, FILTER_FLAG_STRIP_LOW | FILTER_FLAG_NO_ENCODE_QUOTES);
$corpus = filter_input(INPUT_GET, 'corpus', FILTER_SANITIZE_STRING, FILTER_FLAG_STRIP_LOW);

validate_word($word);
validate_corpus($corpus);


$mysqli = mysqli_connect($mysql_server, $mysql_username, $mysql_passwd)
  or die('Could not connect: ' . $mysqli->connect_error);
mysqli_set_charset($mysqli, 'utf8');
mysqli_select_db($mysqli, $main_db_name) or die('Could not select database');

$counts = get_counts($word, $corpus);

mysqli_close($mysqli);

echo json_encode($counts);

?>
