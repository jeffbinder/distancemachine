<?php

// This script computes the percentages of words marked as more common earlier, later
// and both for the publication year of each text in a collection.  Before running this,
// it is necessary to create the table total_count using the query in misc.sql. 

include '../public_html/application.php';


ini_set('memory_limit', '2048M');

$mysqli = mysqli_connect($mysql_server, $mysql_username, $mysql_passwd)
  or die('Could not connect: ' . $mysqli->connect_error);
mysqli_set_charset($mysqli, 'utf8');
mysqli_select_db($mysqli, $main_db_name) or die('Could not select database');

if (count($argv) != 6) {
  die("Specify the corpus, the input directory, the metadata file, the word frequency cutoff, and the output file name!\n");
}
$corpus = $argv[1];
$indir = $argv[2];
$metadata_file = $argv[3];
$cutoff_freq = $argv[4];
$outfile = $argv[5];

$mysqli->query("SET max_allowed_packet=4G");

$metadata = [];
foreach (explode("\n", file_get_contents($metadata_file)) as $row) {
    if ($row == "") {
        continue;
    }
    $d = explode("\t", $row);
    $uri = $d[0];
    $metadata[$uri] = ['year' => $d[1], 'title' => $d[2], 'author' => $d[3]];
}

$header_row = ['uri', 'year', 'title', 'author', 'word_count', 'word_types',
               'ncommon_tok',
	       'nold_tok', 'nnew_tok', 'nlapsed_tok', 'ncommon_typ', 'nold_typ',
	       'nnew_typ', 'nlapsed_typ', /*'nabsent', 'n<10000000000', 'n<1000000000',
					    'n<100000000', 'n<10000000', 'n<1000000'*/];
/*foreach ($dicts as $dict) {
  $header_row[] = 'abs-' . $dict;
  $header_row[] = 'obs-' . $dict;
  $header_row[] = 'vul-' . $dict;
  }*/

$f = fopen($outfile, "w");
fwrite($f, implode($header_row, "\t") . "\n");

load_common_word_list($corpus, $cutoff_freq);

foreach (scandir($indir) as $filename) {
  if ($filename == "." || $filename == "..") {
    continue;
  }

  echo $filename . "\n";

  $uri = pathinfo($filename, PATHINFO_FILENAME);
  $uri = explode("_", $uri)[1];
  $year = $metadata[$uri]['year'];
  $title = $metadata[$uri]['title'];
  $author = $metadata[$uri]['author'];

  $filename = $indir . "/" . $filename;
  if (is_uri_registered($corpus, $uri)
      || ($year != 'n.d.' && ($year < $data_start_year[$corpus]
			      || $year > $data_end_year[$corpus]))) {
    continue;
  }
  /*if (filesize($filename) > 10000) {
    continue;
    }*/
  $text = file_get_contents($filename);

  $data = gen_annotated_text(null, $text, $title, $corpus, true, true, 0);
  $content = $data['content'];
  $word_count = $data['word_count'];
  $word_types = $data['word_types'];
  
  $stats = compute_text_stats($content, $word_count, $word_types,
			      $corpus, $cutoff_freq);
  $usage_stats = $stats['usage_stats'];
  $freq_stats = $stats['freq_stats'];
  $dict_stats = $stats['dict_stats'];

  $i = $year - $start_year[$corpus];
  $output_row = [$uri, $year, $title, $author, $word_count, $word_types,
		 $usage_stats['ctok'][$i], $usage_stats['otok'][$i],
		 $usage_stats['ntok'][$i], $usage_stats['ltok'][$i],
		 $usage_stats['ctyp'][$i], $usage_stats['otyp'][$i],
		 $usage_stats['ntyp'][$i], $usage_stats['ltyp'][$i]];

  /*$output_row[] = $freq_stats['a'];
  $output_row[] = $freq_stats['l'][10000000000];
  $output_row[] = $freq_stats['l'][1000000000];
  $output_row[] = $freq_stats['l'][100000000];
  $output_row[] = $freq_stats['l'][10000000];
  $output_row[] = $freq_stats['l'][1000000];*/

  /*foreach ($dicts as $dict) {
    $output_row[] = $dict_stats['x'][$dict];
    $output_row[] = $dict_stats['o'][$dict];
    $output_row[] = $dict_stats['v'][$dict];
    }*/
  
  fwrite($f, implode($output_row, "\t") . "\n");
  fflush($f);
}

mysqli_close($mysqli);

?>