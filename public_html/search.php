<!DOCTYPE html>
<?php

include 'application.php';

check_archive_mode();

$corpus = filter_input(INPUT_GET, 'corpus', FILTER_SANITIZE_STRING);
validate_corpus($corpus);

?>
<html>
 <head>
  <meta http-equiv="Content-Type" content="text/html; charset=utf-8">
  <link rel="apple-touch-icon" sizes="57x57" href="/apple-touch-icon-57x57.png">
  <link rel="apple-touch-icon" sizes="72x72" href="/apple-touch-icon-72x72.png">
  <link rel="apple-touch-icon" sizes="60x60" href="/apple-touch-icon-60x60.png">
  <link rel="apple-touch-icon" sizes="76x76" href="/apple-touch-icon-76x76.png">
  <link rel="icon" type="image/png" href="/favicon-96x96.png" sizes="96x96">
  <link rel="icon" type="image/png" href="/favicon-16x16.png" sizes="16x16">
  <meta name="msapplication-TileColor" content="#da532c">
  <meta http-equiv="Content-Type" content="text/html; charset=utf-8">
  <script src="http://d3js.org/d3.v3.min.js" charset="utf-8"></script>
  <script src="http://ajax.googleapis.com/ajax/libs/jquery/1.11.0/jquery.min.js"></script>
  <script src="jquery.watermark.min.js"></script>
  <script src="config.js"></script>
  <script src="search.js"></script>
  <link rel="stylesheet" type="text/css" href="http://fonts.googleapis.com/css?family=Inika:700">
  <link rel="stylesheet" type="text/css" href="http://fonts.googleapis.com/css?family=Ubuntu:400">
  <link rel="stylesheet" type="text/css" href="http://fonts.googleapis.com/css?family=Ubuntu:700">
  <link rel="stylesheet" type="text/css" href="site.css">
  <link rel="stylesheet" type="text/css" href="wordinfo.css">
  <link rel="stylesheet" type="text/css" href="search.css">
  <title>The Distance Machine - Search</title>
  <script>
<?php
echo "corpus = " . json_encode($corpus) . ";\n";
?>
  </script>
 </head>
 <body>
 <div id="main-area">
  <div class="box" id="text-area">
   <div style="font-size:16pt;margin-bottom:10px">
    Search the <span id="corpus-name"></span> corpus:
   </div>
   <div class="text">
    <input id="search-box"></input>
   </div>
   <div class="text">
    Time period: <input id="ymin-box"></input> &ndash;
    <input id="ymax-box"></input>
   </div>
   <div id="search-chart">
   </div>
  </div>
  <div class="box" id="result-box">
   <div id="result-area" class="text">
    Your results will appear here.
   </div>
  </div>
 </body>
</html>
