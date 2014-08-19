<?php

include 'application.php';

header('Content-Type: application/json');

$word = filter_input(INPUT_GET, 'word', FILTER_SANITIZE_STRING, FILTER_FLAG_STRIP_LOW);
$corpus = filter_input(INPUT_GET, 'corpus', FILTER_SANITIZE_STRING, FILTER_FLAG_STRIP_LOW);

validate_word($word);
validate_corpus($corpus);


$mysqli = mysqli_connect($mysql_server, $mysql_username, $mysql_passwd)
  or die('Could not connect: ' . $mysqli->connect_error);
mysqli_select_db($mysqli, $main_db_name) or die('Could not select database');

$counts = get_counts($word, $corpus);
$periods = get_usage_periods($word, $corpus);

mysqli_close($mysqli);


$mysqli = mysqli_connect($mysql_server, $mysql_username, $mysql_passwd)
  or die('Could not connect: ' . $mysqli->connect_error);
mysqli_select_db($mysqli, $wordnet_db_name) or die('Could not select database');

$wordnet_definitions = get_wordnet_definitions($word);
$dict_definitions = get_dict_definitions($word);

mysqli_close($mysqli);

        
echo json_encode(["counts" => $counts, "periods" => $periods,
                  "wordnet-definitions" => $wordnet_definitions,
                  "dict-definitions" => $dict_definitions]);

?>
