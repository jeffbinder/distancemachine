<?php

include 'connection.php';

$mysqli = mysqli_connect($mysql_server, $mysql_username, $mysql_passwd)
  or die('Could not connect: ' . $mysqli->connection_error);
mysqli_select_db($mysqli, $main_db_name) or die('Could not select database');

$query = "
SELECT id, INET_NTOA(uploader), title, total_characters, characters_completed,
    cache_hits / words_completed, start_time, CURRENT_TIMESTAMP - start_time
FROM task
WHERE status = 'running'
ORDER BY start_time
";

$result = $mysqli->query($query) or die('Query failed: ' . $mysqli->error);
while ($row = $result->fetch_array(MYSQLI_NUM)) {
    echo "\n";
    echo $row[2] . "\n";
    echo sprintf("uploaded by %s - id %s\n", $row[1], $row[0]);
    echo sprintf("%s / %s (%d%%)\n", $row[4], $row[3],
                 intval($row[4] * 100 / $row[3]));
    if ($row[5]) {
        echo sprintf("cache hit rate (so far): %d%%\n", intval($row[5] * 100));
    }
    echo sprintf("started %s\n", $row[6]);
    echo sprintf("running for %s sec\n", $row[7]);
}

mysqli_close($mysqli);

?>