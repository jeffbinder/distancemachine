<?php

include 'config.php';

$mysqli = mysqli_connect($mysql_server, $mysql_username, $mysql_passwd)
  or die('Could not connect: ' . $mysqli->connect_error);
mysqli_set_charset($mysqli, 'utf8');
mysqli_select_db($mysqli, $main_db_name) or die('Could not select database');

$id = filter_input(INPUT_GET, 'id', FILTER_SANITIZE_STRING, FILTER_FLAG_STRIP_LOW);

if (!(is_string($id) && preg_match('/^[A-Za-z0-9]+$/', $id) && strlen($id) <= 9)) {
    die("Invalid text ID!");
}

// Not using the function so that we don't have to include application.php (for
// performance reasons).
$query = $mysqli->prepare("SELECT total_characters, characters_completed
FROM task WHERE id = ?");
$query->bind_param('s', $id);
$query->execute() or die('Query failed: ' . $mysqli->error);
$query->bind_result($total_characters, $characters_completed);
if ($query->fetch()) {
    if ((int)$total_characters == 0) {
        $progress = 0;
    } else {
        $progress = round($characters_completed / $total_characters * 100);
    }
} else {
    die("Invalid text ID!");
}

mysqli_close($mysqli);

echo $progress;

?>
