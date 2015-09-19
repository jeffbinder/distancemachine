<!DOCTYPE html>
<?php

include 'application.php';

$mysqli = mysqli_connect($mysql_server, $mysql_username, $mysql_passwd)
  or die('Could not connect: ' . $mysqli->connect_error);
mysqli_set_charset($mysqli, 'utf8');
mysqli_select_db($mysqli, $main_db_name) or die('Could not select database');

if ($_SERVER['REQUEST_METHOD'] == "POST") {
    $id = filter_input(INPUT_POST, 'id', FILTER_SANITIZE_STRING, FILTER_FLAG_STRIP_LOW);
    $initial_year = null;
    $initial_freq = $max_freq;
    $initial_highlight_option = null;
} else {
    $id = filter_input(INPUT_GET, 'id', FILTER_SANITIZE_STRING, FILTER_FLAG_STRIP_LOW);
    $initial_year = filter_input(INPUT_GET, 'y', FILTER_SANITIZE_NUMBER_INT);
    if (is_null($initial_year)) {
        $initial_year = null;
    } else {
        $initial_year = (int)$initial_year;
    }
    $initial_freq = filter_input(INPUT_GET, 'f', FILTER_SANITIZE_NUMBER_INT);
    if (is_null($initial_freq)) {
        $initial_freq = $max_freq;
    } else {
        $initial_freq = (int)$initial_freq;
    }
    $initial_highlight_option = filter_input(INPUT_GET, 'd', FILTER_SANITIZE_STRING, FILTER_FLAG_STRIP_LOW);
    if ($initial_highlight_option && $initial_highlight_option != "freq") {
        validate_dict($initial_highlight_option);
        $initial_highlight_option = 'dict-' . $initial_highlight_option;
    }
}

validate_id($id);

$saved = is_text_saved($id);
if ($saved) {
    $data = get_saved_text($id);
} else {
    $data = get_text_from_tmp_file($id);
}
$corpus = $data['corpus'];
$title = $data['title'];
$content = $data['content'];

if (is_null($initial_year)) {
  $initial_year = $end_year[$corpus];
}
validate_year($initial_year, $corpus);

?>
<html>
 <head>
  <meta http-equiv="Content-Type" content="text/html; charset=utf-8">
  <meta name="viewport" content="maximum-scale=1, user-scalable=no">
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
  <title>The Distance Machine | <?php echo htmlspecialchars($title); ?></title>
  <script>
archive = false;
<?php

echo "id = " . json_encode($id) . ";\n";
echo "initial_year = " . json_encode($initial_year) . ";\n";
echo "initial_freq = " . json_encode($initial_freq) . ";\n";
echo "initial_highlight_option = " . json_encode($initial_highlight_option) . ";\n";
echo "corpus = " . json_encode($corpus) . ";\n";
echo "title = " . json_encode($title) . ";\n";
echo "saved = " . json_encode($saved) . ";\n";

// Preload the total word counts, since they may be used multiple times.
$totals = [$corpus => get_totals($corpus)];
echo "totals = " . json_encode($totals) . ";\n";

// Get the word count of the text.
echo "word_count = " . json_encode(get_word_count($id)) . ";\n";

?>
q = null;
  </script>
 </head>
 <body>
  <div id="header-loading">
   Loading text...
  </div>
  <div id="header">
   <div id="option-area" style="float:left">
    <div>
     <select id="highlight-option">
       <option value="ngrams">Highlighting words that are more common earlier or later than:</option>
       <option value="freq">Highlighting words with avg frequency less than:</option>
      </select>
    </div>
    <div>
     <div id="slider-value-area" style="float:right"><span class="slider-value"></span></div>
     <div id="slider" style="float:right"></div>
     <div style="float:right"><button id="play-button"></button></div>
    </div>
    <div style="clear:both"><a href="javascript:push_history();show_word_list()">Show list of highlighted words</a> &ndash; <a href="javascript:push_history();show_stats_box()">overall stats for document</a></div>
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
     <div id="print-header-text" style="margin-top:1em"></div>
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
    <div class="back-button"><a href="javascript:pop_history()">&lt;back</a></div>
    <div>Selected word: <span id="selected-word"></span> (<a href="" id="word-search-link">search</a>)</div>
    <hr />
    Frequency in the <span class="corpus-name"></span> corpus (click to view):
    <div id="word-usage-chart"></div>
    <div><span id="usage-periods-text"></span></div>
    <hr />
    <div id="definition-area"><span id="definitions"></span></div>
  </div>
  <div id="reverse-lookup-box">
    <div class="back-button"><a href="javascript:pop_history()">&lt;back</a></div>
    <div id="reverse-lookup-text"></div>
    <hr />
    <div id="reverse-lookup-word-area"><span id="reverse-lookup-words"></span></div>
  </div>
  <div id="stats-box">
    <div class="back-button"><a href="javascript:pop_history()">&lt;back</a></div>
    <div>Word usage statistics for &ldquo;<span class="document-title"></span>&rdquo;</div>
    <hr />
    Percentage of words uncommon in each year:
    <div id="document-chart"></div>
    <div>The lines show the % of words more common <span class="old-word">earlier</span>, <span class="new-word">later</span>, or <span class="lapsed-word">both</span>.</div>
    <hr />
    <div id="stats-area">
      <div id="selected-year-stats"></div>
      <hr />
      <div id="freq-stats"></div>
      <hr />
      <div id="dictionary-stats"></div>
    </div>
  </div>
  <div id="save-box">
    <div>This text has been saved!  Copy this link to share it or access it in the future:</div>
    <div style="margin-top:10px">
     <input id="url-area" type="text" name="link" style="width:400px">
    </div>
  </div>
  <div id="word-list">
    <div class="back-button"><a href="javascript:pop_history()">&lt;back</a></div>
    <div><span id="word-list-option-text"></span></div>
    <hr />
    <div id="word-list-area"></div>
  </div>
  <div id="save-error-box">
    <div>The server encountered an error saving the text.</div>
  </div>
  <div id="help-box">
    <div><b>Help</b></div>
    <div class="key" id="ngrams-key" style="clear:both">
     <div><span class="old-word">blue</span> words are more common earlier</div>
     <div><span class="new-word">red</span> words are more common later</div>
     <div><span class="lapsed-word">yellow</span> words are more common both earlier and later</div>
    </div>
    <div class="key" id="freq-key" style="clear:both">
     <div><span class="rare-word">blue</span> words have average frequency below the selected value</div>
     <div><span class="absent-word">red</span> words are not found in the corpus at all</div>
    </div>
    <div class="key" id="dict-key" style="clear:both">
     <div><span class="omitted-word">red</span> words are omitted from the selected dictionary</div>
     <div><span class="obsolete-word">blue</span> words are marked as rare or obsolete</div>
     <div><span class="vulgar-word">yellow</span> words are marked as vulgar, colloquial, or improper</div>
    </div>
    <div>
     This interactive text was created by <a href="/" target="_blank">The Distance Machine</a>, a tool that uses historical data from Google Books to identify words that were uncommon at a given point in time.
    </div>
    <div>
     Use the controls at the top left to see what words were uncommon in different years, to find words in the text that were omitted from a given dictionary, or to find very uncommon words.  When highlighting words by year or frequency, you can also click the play button to animate.  Double-click or tap on words in the text to see details, including the full historical usage data and dictionary entries.
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
