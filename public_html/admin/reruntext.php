<?php

include '../application.php';

$id = filter_input(INPUT_GET, 'id', FILTER_SANITIZE_STRING, FILTER_FLAG_STRIP_LOW | FILTER_FLAG_NO_ENCODE_QUOTES);

$mysqli = mysqli_connect($mysql_server, $mysql_username, $mysql_passwd)
  or die('Could not connect: ' . $mysqli->connection_error);
mysqli_select_db($mysqli, $main_db_name) or die('Could not select database');

validate_id($id);
$data = get_saved_text($id);

$corpus = $data['corpus'];
$title = $data['title'];
$content = $data['content'];

$text = strip_annotations($content);

reset_task_start_time($id);
gen_annotated_text($id, $text, $title, $corpus, true);
save_text($id);

mysqli_close($mysqli);

?>