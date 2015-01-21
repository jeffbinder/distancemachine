<?php

include '../config.php';

$mysqli = mysqli_connect($mysql_server, $mysql_username, $mysql_passwd)
  or die('Could not connect: ' . $mysqli->connection_error);
mysqli_select_db($mysqli, $main_db_name) or die('Could not select database');

$query = "
SELECT title, words_completed, cache_hits / words_completed, end_time, start_time, id, INET_NTOA(uploader), end_time - start_time
FROM task
WHERE status = 'saved'
ORDER BY end_time DESC
";

$result = $mysqli->query($query) or die('Query failed: ' . $mysqli->error);
$first = true;
while ($row = $result->fetch_array(MYSQLI_NUM)) {
    if (!$first) {
        echo "<br/>";
    }
    $first = false;
    echo "<div><a href='/text/" . $row[5] . "'>" . $row[0] . "</a>";
    if ($log_ip_addresses) {
        echo sprintf("<br/>uploaded by %s", $row[6]);
    }
    echo sprintf("<br/>%s words - completed %s", $row[1], $row[3]);
    echo sprintf("<br/>processed in %s sec", $row[7]);
    if ($row[2]) {
        echo sprintf(" - cache hit rate: %d%%\n", intval($row[2] * 100));
    }
    echo "<br/><a href='javascript:rerun_text(\"" . $row[5] . "\")'>rerun</a>";
    echo " - <a href='javascript:delete_text(\"" . $row[5] . "\")'>delete</a>";
    echo "</div>";
}

mysqli_close($mysqli);

?>