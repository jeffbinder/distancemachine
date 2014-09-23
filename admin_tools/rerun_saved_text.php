<?php

set_include_path('../public_html/');
include 'application.php';

if (count($argv) != 2) {
    die("Specify the ID of the text to return.");
}
$id = $argv[1];

$mysqli = mysqli_connect($mysql_server, $mysql_username, $mysql_passwd)
  or die('Could not connect: ' . $mysqli->connection_error);
mysqli_select_db($mysqli, $main_db_name) or die('Could not select database');

echo "Rerunning " . $id . "\n";

$data = get_saved_text($id);

$corpus = $data['corpus'];
$title = $data['title'];
$content = $data['content'];

echo "Title: " . $title . "\n";

$text = strip_annotations($content);

gen_annotated_text($id, $text, $title, $corpus, true);
save_text($id);

mysqli_close($mysqli);

?>