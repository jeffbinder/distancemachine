<?php

include '../config.php';

$mysqli = mysqli_connect($mysql_server, $mysql_username, $mysql_passwd)
  or die('Could not connect: ' . $mysqli->connection_error);
mysqli_select_db($mysqli, $main_db_name) or die('Could not select database');

$query = "
SELECT title, status, words_completed, cache_hits / words_completed as cache_hit_rate,
    start_time, end_time, id, INET_NTOA(uploader) as uploader, timediff(NOW(), start_time), characters_completed * 100 / total_characters
FROM task
WHERE status = 'running'
ORDER BY end_time DESC
LIMIT 10
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
    echo sprintf("<br/>started %s - %s words complete (%d%%)", $row[4], $row[2], $row[9]);
    echo sprintf("<br/>running for %s", $row[8]);
    if ($row[3]) {
        echo sprintf(" - cache hit rate: %d%%\n", intval($row[3] * 100));
    }
    echo "<br/><a href='javascript:cancel_task(\"" . $row[6] . "\")'>cancel</a>";
    echo "</div>";
}

if ($first) {
    echo "<div>There are no tasks running at present.</div>";
}

mysqli_close($mysqli);

?>