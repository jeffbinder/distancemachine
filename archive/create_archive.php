<?php

include '../public_html/application.php';

ini_set('memory_limit', '2048M');

$mysqli = mysqli_connect($mysql_server, $mysql_username, $mysql_passwd)
  or die('Could not connect: ' . $mysqli->connect_error);
mysqli_set_charset($mysqli, 'utf8');
mysqli_select_db($mysqli, $main_db_name) or die('Could not select database');

if (count($argv) != 5) {
  die("Specify the corpus, the input directory, the metadata file, and the output directory!\n");
}
$corpus = $argv[1];
$indir = $argv[2];
$metadata_file = $argv[3];
$outdir = $argv[4];

$mysqli->query("SET max_allowed_packet=4G");

$metadata = [];
foreach (explode("\n", file_get_contents($metadata_file)) as $row) {
    $d = explode("\t", $row);
    $uri = $d[0];
    $metadata[$uri] = ['year' => $d[1], 'title' => $d[2], 'author' => $d[3]];
}

foreach (scandir($indir) as $filename) {
  if ($filename == "." || $filename == "..") {
    continue;
  }

  echo $filename . "\n";

  $uri = pathinfo($filename, PATHINFO_FILENAME);
  $uri = explode("_", $uri)[1];
  $out_filename = $outdir . "/" . $uri;
  $year = $metadata[$uri]['year'];
  $title = $metadata[$uri]['title'];
  $author = $metadata[$uri]['author'];

  $filename = $indir . "/" . $filename;
  if (is_uri_registered($corpus, $uri) || $year < $data_start_year[$corpus]
      || $year > $data_end_year[$corpus]) {
    continue;
  }
  $text = file_get_contents($filename);

  $data = gen_annotated_text(null, $text, $title, $corpus, true, false, 0);
  $content = $data['content'];
  $word_count = $data['word_count'];

  file_put_contents($out_filename, $content);
  create_text_entry($corpus, $uri, $title, $author, $year, $word_count, $text);
}

mysqli_close($mysqli);

?>
