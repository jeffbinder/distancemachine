<!DOCTYPE html>
<?php

include 'application.php';

$mysqli = mysqli_connect($mysql_server, $mysql_username, $mysql_passwd)
  or die('Could not connect: ' . $mysqli->connect_error);
mysqli_select_db($mysqli, $main_db_name) or die('Could not select database');

if ($_SERVER['REQUEST_METHOD'] == "POST") {
    $id = filter_input(INPUT_POST, 'id', FILTER_SANITIZE_STRING, FILTER_FLAG_STRIP_LOW);
} else {
    $id = filter_input(INPUT_GET, 'id', FILTER_SANITIZE_STRING, FILTER_FLAG_STRIP_LOW);
}

validate_id($id);

$saved = is_text_saved($id);
if ($saved) {
    $data = get_saved_text($id);
} else {
    $data = get_text_from_tmp_file($id);
}
$region = $data['region'];
$title = $data['title'];
$content = $data['content'];

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
  <script src="http://d3js.org/d3.v3.min.js" charset="utf-8"></script>
  <script src="http://ajax.googleapis.com/ajax/libs/jquery/1.11.0/jquery.min.js"></script>
  <!--<script src="d3.min.js"></script>-->
  <!--<script src="jquery.js"></script>-->
  <script src="jquery-ui-1.10.4.custom.min.js"></script>
  <script src="jquery.ui.touch-punch.min.js"></script>
  <script src="jquery.cookie.js"></script>
  <script src="config.js"></script>
  <script src="wordinfo.js"></script>
  <script src="text.js"></script>
  <link rel="stylesheet" type="text/css" href="http://fonts.googleapis.com/css?family=Inika">
  <link rel="stylesheet" type="text/css" href="http://fonts.googleapis.com/css?family=Ubuntu:400">
  <link rel="stylesheet" type="text/css" href="http://fonts.googleapis.com/css?family=Ubuntu:700">
  <link rel="stylesheet" type="text/css" href="site.css">
  <link rel="stylesheet" type="text/css" href="wordinfo.css">
  <link rel="stylesheet" type="text/css" href="text.css">
  <!--<link rel="stylesheet" type="text/css" href="jquery-ui.min.css">-->
  <link rel="stylesheet" href="http://ajax.googleapis.com/ajax/libs/jqueryui/1.10.4/themes/humanity/jquery-ui.css" type="text/css" media="all">
  <title><?php echo htmlspecialchars($title); ?></title>
  <script>
<?php

echo "id = " . json_encode($id) . ";\n";
echo "region = " . json_encode($region) . ";\n";
echo "title = " . json_encode($title) . ";\n";
echo "saved = " . json_encode($saved) . ";\n";

// Preload the total word counts, since they may be used multiple times.
$totals = [$region => get_totals($region)];
echo "totals = " . json_encode($totals) . ";\n";

?>
  </script>
 </head>
 <body>
  <div id="header-loading">
   Loading text...
  </div>
  <div id="header">
   <div id="option-area" style="float:left">
    <div>Slide to change the year &ndash; double-click words to see details</div>
    <div>
     <div id="slider" style="float:right"></div>
     <div style="float:right"><button id="play-button"></button></div>
    </div>
    <div>Highlighting words uncommon in: <span class="selected-year"></span></div>
   </div>
   <div id="option-area-right" style="float:right">
    <div style="float:right;margin-right:19px;margin-top:2px;margin-bottom:3px">
     <a href="javascript:show_help_box()">Help</a> &ndash;
     <a href="javascript:print_text()">Print</a> &ndash;
     <a href="javascript:save_text()" id="save-link">Save this text</a>
    </div>
    <div id="controls" style="clear:both;text-align:right">
     <div style="margin-top:2px;padding-left:3px">
      Highlight type: <select id="highlight-type">
       <option value="bg">Background color</option>
       <option value="box">Box around word</option>
       <option value="none">No highlighting</option>
      </select>
     </div>
     <div style="margin-top:2px;padding-left:3px">
      Look up word: <input type="text" id="word-lookup">
     </div>
    </div>
   </div>
  </div>
  <div id="main-area">
   <div id="print-header">
    <div>
     <div><span id="print-title"></span></div>
     <div style="margin-top:1em">Highlighting words uncommon in: <span class="selected-year"></span></div>
    </div>
   </div>
   <div id="text-area">
    <?php echo $content; ?>
   </div>
   <div id="use-print-link-message">
    To print texts created using the Distance Machine, please use the &ldquo;Print&rdquo; link in the upper-right corner.
   </div>
  </div>
  <div id="word-info">
    <div>Selected word: <span id="selected-word"></span></div>
    <hr />
    Frequency in the Google Books <span id="corpus-name"></span> corpus:
    <div id="word-usage-chart"></div>
    <div><span id="usage-periods-text"></span></div>
    <hr />
    <span id="definition-text"><a href="http://wordnet.princeton.edu/" target="_blank">WordNet</a> definitions:</span>
    <div id="definition-area"><span id="definitions"></span></div>
  </div>
  <div id="save-box">
    <div>This text has been saved!  Copy this link to share it or access it in the future:</div>
    <div style="margin-top:10px">
     <input id="url-area" type="text" name="link" style="width:400px">
    </div>
  </div>
  <div id="save-error-box">
    <div>The server encountered an error saving the text.</div>
  </div>
  <div id="help-box">
    <div><b>Help</b></div>
    <div id="key" style="clear:both">
     <div><span class="old-word">blue</span> words are more common earlier</div>
     <div><span class="new-word">red</span> words are more common later</div>
     <div><span class="lapsed-word">yellow</span> words are more common both earlier and later</div>
    </div>
    <div>
     This interactive text was created by <a href="/" target="_blank">The Distance Machine</a>, a tool that uses historical data from Google books to identify words that were uncommon at a given point in time.
    </div>
    <div>
     Use the slider on the top to see what words were uncommon in different years, or click the play button to animate.  You can also double-click on words in the text to see details.
    </div>
    <div>
     <a href="/about" target="_blank">About this program</a>
     | <a href="/howitworks" target="_blank">How it works</a>
     | <a href="/tos" target="_blank">Legal</a>
    </div>
    <div id="help-cookie-message">
     This box has appeared because it is your first time opening this tool.  You can open
     it again by clicking &ldquo;Help&rdquo; at the upper-right corner of the window.
     Click on the text to dismiss.
    </div>
  </div>
 </body>
</html>
<?php

mysqli_close($mysqli);

?>
