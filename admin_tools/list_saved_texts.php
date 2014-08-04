<?php

include 'connection.php';

$mysqli = mysqli_connect($mysql_server, $mysql_username, $mysql_passwd)
  or die('Could not connect: ' . $mysqli->connection_error);
mysqli_select_db($mysqli, $main_db_name) or die('Could not select database');

$query = "
SELECT title, words_completed, cache_hits / words_completed, end_time, start_time, id, INET_NTOA(uploader), end_time - start_time
FROM task
WHERE status = 'saved'
ORDER BY end_time
";

$result = $mysqli->query($query) or die('Query failed: ' . $mysqli->error);
while ($row = $result->fetch_array(MYSQLI_NUM)) {
    echo "\n";
    echo $row[0] . "\n";
    echo sprintf("uploaded by %s\n", $row[6]);
    echo sprintf("%s words - completed %s\n", $row[1], $row[3]);
    echo sprintf("completed in %s sec\n", $row[7]);
    if ($row[2]) {
        echo sprintf("cache hit rate (so far): %d%%\n", intval($row[2] * 100));
    }
    echo $domain . "/text/" . $row[5] . "\n";
}

mysqli_close($mysqli);

?>