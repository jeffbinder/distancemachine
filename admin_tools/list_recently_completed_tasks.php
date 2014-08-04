<?php

include 'connection.php';

$mysqli = mysqli_connect($mysql_server, $mysql_username, $mysql_passwd)
  or die('Could not connect: ' . $mysqli->connection_error);
mysqli_select_db($mysqli, $main_db_name) or die('Could not select database');

$query = "
SELECT title, status, words_completed, cache_hits / words_completed as cache_hit_rate,
    start_time, end_time, id, INET_NTOA(uploader) as uploader, end_time - start_time
FROM task
WHERE status IN ('completed', 'saved', 'deleted')
ORDER BY end_time DESC
LIMIT 10
";

$result = $mysqli->query($query) or die('Query failed: ' . $mysqli->error);
while ($row = $result->fetch_array(MYSQLI_NUM)) {
    echo "\n";
    echo $row[0] . "\n";
    echo sprintf("uploaded by %s\n", $row[7]);
    echo sprintf("%s words - %s sec\n", $row[2], $row[8]);
    echo sprintf("started %s, completed %s\n", $row[4], $row[5]);
    if ($row[3]) {
        echo sprintf("cache hit rate (so far): %d%%\n", intval($row[3] * 100));
    }
    echo $domain . "/text/" . $row[6] . "\n";
}

mysqli_close($mysqli);

?>