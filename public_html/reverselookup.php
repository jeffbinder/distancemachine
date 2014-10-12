<?php

include 'application.php';

header('Content-Type: application/json');

$word = filter_input(INPUT_GET, 'word', FILTER_SANITIZE_STRING, FILTER_FLAG_STRIP_LOW | FILTER_FLAG_NO_ENCODE_QUOTES);
$dict = filter_input(INPUT_GET, 'dict', FILTER_SANITIZE_STRING, FILTER_FLAG_STRIP_LOW);

validate_word($word);
validate_dict($dict);


$mysqli = mysqli_connect($mysql_server, $mysql_username, $mysql_passwd)
  or die('Could not connect: ' . $mysqli->connect_error);
mysqli_set_charset($mysqli, 'utf8');
mysqli_select_db($mysqli, $wordnet_db_name) or die('Could not select database');

$headwords = reverse_dict_lookup($word, $dict);

mysqli_close($mysqli);

        
echo json_encode(["headwords" => $headwords]);

?>
