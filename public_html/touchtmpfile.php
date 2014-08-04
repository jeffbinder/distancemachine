<?php

include 'application.php';

$mysqli = mysqli_connect($mysql_server, $mysql_username, $mysql_passwd)
  or die('Could not connect: ' . $mysqli->connect_error);
mysqli_select_db($mysqli, $main_db_name) or die('Could not select database');

$id = filter_input(INPUT_POST, 'id', FILTER_SANITIZE_STRING, FILTER_FLAG_STRIP_LOW);

validate_id($id);
if (is_text_completed_and_unsaved($id)) {
    touch_tmp_file($id);
}

mysqli_close($mysqli);

?>
