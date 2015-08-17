<?php

include 'application.php';

header('Content-Type: application/json');

check_archive_mode();

$q = filter_input(INPUT_GET, 'q', FILTER_SANITIZE_STRING, FILTER_FLAG_STRIP_LOW | FILTER_FLAG_NO_ENCODE_QUOTES);
$corpus = filter_input(INPUT_GET, 'corpus', FILTER_SANITIZE_STRING, FILTER_FLAG_STRIP_LOW);
$uri = filter_input(INPUT_GET, 'uri', FILTER_SANITIZE_STRING, FILTER_FLAG_STRIP_LOW);

validate_search_string($q);
validate_corpus($corpus);

$mysqli = mysqli_connect($mysql_server, $mysql_username, $mysql_passwd)
  or die('Could not connect: ' . $mysqli->connect_error);
mysqli_set_charset($mysqli, 'utf8');
mysqli_select_db($mysqli, $main_db_name) or die('Could not select database');

validate_archive_uri($corpus, $uri);

$data = get_excerpts($corpus, $uri, $q, $excerpt_length, $num_excerpts);

mysqli_close($mysqli);

echo json_encode($data);

?>
