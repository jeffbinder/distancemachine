<?php

set_include_path('../public_html/');
include 'application.php';

$mysqli = mysqli_connect($mysql_server, $mysql_username, $mysql_passwd)
  or die('Could not connect: ' . $mysqli->connection_error);
mysqli_select_db($mysqli, $main_db_name) or die('Could not select database');

$ids = [];

$query = "
SELECT id
FROM task
WHERE status = 'saved'
";
$result = $mysqli->query($query) or die('Query failed: ' . $mysqli->error);
while ($row = $result->fetch_array(MYSQLI_NUM)) {
    $id = $row[0];
    $ids[] = $id;
}

foreach ($ids as $id) {
    echo "Rerunning " . $id . "\n";

    $data = get_saved_text($id);
    
    $corpus = $data['corpus'];
    $title = $data['title'];
    $content = $data['content'];
    
    echo "Title: " . $title . "\n";
    
    $text = strip_annotations($content);
    
    reset_task_start_time($id);
    gen_annotated_text($id, $text, $title, $corpus, true);
    save_text($id);
}

mysqli_close($mysqli);

?>