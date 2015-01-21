<?php

include '../config.php';

$mysqli = mysqli_connect($mysql_server, $mysql_username, $mysql_passwd)
  or die('Could not connect: ' . $mysqli->connection_error);
mysqli_select_db($mysqli, $main_db_name) or die('Could not select database');

$query = "
SELECT title, status, words_completed, cache_hits / words_completed as cache_hit_rate,
    start_time, end_time, id, INET_NTOA(uploader) as uploader, end_time - start_time
FROM task
WHERE status IN ('aborted', 'completed', 'saved', 'deleted')
ORDER BY end_time DESC
LIMIT 25
";

$result = $mysqli->query($query) or die('Query failed: ' . $mysqli->error);
$first = true;
while ($row = $result->fetch_array(MYSQLI_NUM)) {
    if (!$first) {
        echo "<br/>";
    }
    $first = false;
    echo "<div><a href='/text/" . $row[6] . "'>" . $row[0] . "</a>";
    echo sprintf(" (%s)", $row[1]);
    if ($log_ip_addresses) {
        echo sprintf("<br/>uploaded by %s", $row[7]);
    }
    echo sprintf("<br/>%s words - completed %s", $row[2], $row[5]);
    echo sprintf("<br/>processed in %s sec", $row[8]);
    if ($row[3]) {
        echo sprintf(" - cache hit rate: %d%%\n", intval($row[3] * 100));
    }
    echo "<br/><a href='javascript:delete_text(\"" . $row[6] . "\")'>delete</a>";
    echo "</div>";
}

mysqli_close($mysqli);

?>