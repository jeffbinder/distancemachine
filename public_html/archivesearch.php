<?php

include 'application.php';

header('Content-Type: application/json');

check_archive_mode();

$q = filter_input(INPUT_GET, 'q', FILTER_SANITIZE_STRING, FILTER_FLAG_STRIP_LOW | FILTER_FLAG_NO_ENCODE_QUOTES);
$corpus = filter_input(INPUT_GET, 'corpus', FILTER_SANITIZE_STRING, FILTER_FLAG_STRIP_LOW);
$ymin = filter_input(INPUT_GET, 'ymin', FILTER_VALIDATE_INT);
$ymax = filter_input(INPUT_GET, 'ymax', FILTER_VALIDATE_INT);

if (is_null($ymin)) {
  $ymin = $start_year[$corpus];
}
if (is_null($ymax)) {
  $ymax = $end_year[$corpus];
}

validate_search_string($q);
validate_corpus($corpus);
validate_year($ymin, $corpus);
validate_year($ymax, $corpus);

$mysqli = mysqli_connect($mysql_server, $mysql_username, $mysql_passwd)
  or die('Could not connect: ' . $mysqli->connect_error);
mysqli_set_charset($mysqli, 'utf8');
mysqli_select_db($mysqli, $main_db_name) or die('Could not select database');

if ($log_word_searches) {
    log_word_search($q);
}

$results = fulltext_search($corpus, $q, $ymin, $ymax);

mysqli_close($mysqli);

echo json_encode($results);

?>
