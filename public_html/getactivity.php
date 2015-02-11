<?php

include 'config.php';

$mysqli = mysqli_connect($mysql_server, $mysql_username, $mysql_passwd)
  or die('Could not connect: ' . $mysqli->connection_error);
mysqli_select_db($mysqli, $main_db_name) or die('Could not select database');

$query = "
SELECT time, word
FROM word_lookup_log
ORDER BY time DESC
LIMIT 20
";

$result = $mysqli->query($query) or die('Query failed: ' . $mysqli->error);
$n = 0;
while ($row = $result->fetch_array(MYSQLI_NUM)) {
    $n += 1;
    $first = false;
    echo "<div>" . $n . ". " . $row[1] . "</div>";
}

mysqli_close($mysqli);

?>