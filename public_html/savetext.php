<?php

include 'application.php';

header('Content-Type: application/json');

$mysqli = mysqli_connect($mysql_server, $mysql_username, $mysql_passwd)
  or die('Could not connect: ' . $mysqli->connect_error);
mysqli_set_charset($mysqli, 'utf8');
mysqli_select_db($mysqli, $main_db_name) or die('Could not select database');

$id = filter_input(INPUT_POST, 'id', FILTER_SANITIZE_STRING, FILTER_FLAG_STRIP_LOW);

validate_id($id);
if (!is_text_completed_and_unsaved($id)) {
    die("Text cannot be saved!");
}

save_text($id);

mysqli_close($mysqli);
        
echo json_encode(true);

?>
