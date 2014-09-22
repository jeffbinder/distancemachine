<?php

include 'config.php';

function validate_id($id)
{
    if (!(is_string($id) && preg_match('/^[A-Za-z0-9]+$/', $id) && strlen($id) <= 9
          && is_id_registered($id))) {
        die("This text could not be found.");
    }
}

function validate_year($year)
{
    global $start_year, $end_year;
    if (!(is_int($year) && $year >= $start_year && $year <= $end_year)) {
        die("Year out of range.");
    }
}

function validate_corpus($corpus)
{
    global $corpora;
    if (!(is_string($corpus) && in_array($corpus, $corpora, true))) {
        die("Invalid corpus!");
    }
}

function validate_title($title)
{
    if (!(is_string($title) && mb_check_encoding($title, 'UTF-8'))) {
        die("Title is not a valid UTF-8 string.");
    }
}

function validate_text($text)
{
    if (!(is_string($text) && mb_check_encoding($text, 'UTF-8'))
        || preg_match('/[\x00-\x08\x0B\x0C\x0E-\x1F\x80-\x9F]/u', $text)) {
        die("Text is not a valid UTF-8 string.");
    }
}

function validate_word($word)
{
    if (!(is_string($word) && mb_check_encoding($word, 'UTF-8') && strlen($word) <= 63)) {
        die("Invalid word.");
    }
}

function validate_classes($classes)
{
    if (!(is_string($classes) && preg_match('/^[nlorx0-9]*$/', $classes))) {
        die("Invalid usage classes!");
    }
}

function validate_dict($dict)
{
    global $dicts;
    if (!(is_null($dict) || (is_string($dict) && in_array($dict, $dicts, true)))) {
        die("Invalid dictionary!");
    }
}

function validate_dicts($dicts)
{
    if (!(is_string($dicts))) {
        die("Invalid dictionary list!");
    }
}

function create_task_entry($id)
{
    global $mysqli;
    global $log_ip_addresses;
    if ($log_ip_addresses) {
        $ip = $_SERVER['REMOTE_ADDR'];
    } else {
        $ip = 0;
    }
    $query = $mysqli->prepare("INSERT INTO task (id, uploader)
VALUES (?, INET_ATON(?))");
    $query->bind_param('ss', $id, $ip);
    $query->execute() or die('Query failed: ' . $mysqli->error);
}

function is_id_registered($id)
{
    global $mysqli;
    $query = $mysqli->prepare("SELECT id FROM task WHERE id = ?");
    $query->bind_param('s', $id);
    $query->execute() or die('Query failed: ' . $mysqli->error);
    $query->store_result();
    return (bool)($query->num_rows > 0);
}

function set_task_data($id, $title, $corpus)
{
    global $mysqli;
    $query = $mysqli->prepare("UPDATE task SET title = ?, corpus = ? WHERE id = ?");
    $query->bind_param('sss', $title, $corpus, $id);
    $query->execute() or die('Query failed: ' . $mysqli->error);
}

function delete_task_data($id)
{
    global $mysqli;
    $query = $mysqli->prepare("DELETE FROM task WHERE id = ?");
    $query->bind_param('s', $id);
    $query->execute() or die('Query failed: ' . $mysqli->error);
}

function update_task_total($id, $total_chars)
{
    global $mysqli;
    $query = $mysqli->prepare("UPDATE task SET total_characters = ? WHERE id = ?");
    $query->bind_param('is', $total_chars, $id);
    $query->execute() or die('Query failed: ' . $mysqli->error);
}

function update_task_progress($id, $nchars, $nwords, $nlines, $nannotations, $cache_hits)
{
    global $mysqli;
    $query = $mysqli->prepare("UPDATE task
SET characters_completed = ?, words_completed = ?,
lines_completed = ?, words_marked = ?, cache_hits = ? WHERE id = ?");
    $query->bind_param('iiiiis', $nchars, $nwords, $nlines, $nannotations, $cache_hits, $id);
    $query->execute() or die('Query failed: ' . $mysqli->error);
}

function get_task_progress_percent($id)
{
    global $mysqli;
    $query = $mysqli->prepare("SELECT total_characters, characters_completed
FROM task WHERE id = ?");
    $query->bind_param('s', $id);
    $query->execute() or die('Query failed: ' . $mysqli->error);
    $query->bind_result($total_characters, $characters_completed);
    if ($query->fetch()) {
        if ((int)$total_characters == 0) {
            $progress = 0;
        } else {
            $progress = round((int)$characters_completed / (int)$total_characters * 100);
        }
    } else {
        $progess = 0;
    }
    $query->free_result();
}

function task_status($id)
{
    global $mysqli;
    $query = $mysqli->prepare("SELECT status FROM task WHERE id = ?");
    $query->bind_param('s', $id);
    $query->execute() or die('Query failed: ' . $mysqli->error);
    $query->bind_result($status);
    if ($query->fetch()) {
        return $status;
    }
    return null;
}

function set_task_running($id)
{
    global $mysqli;
    $query = $mysqli->prepare("UPDATE task SET status = 'running' WHERE id = ?");
    $query->bind_param('s', $id);
    $query->execute() or die('Query failed: ' . $mysqli->error);
}

function set_task_completed($id)
{
    global $mysqli;
    $query = $mysqli->prepare("UPDATE task SET end_time = CURRENT_TIMESTAMP, status = 'completed'
WHERE id = ?");
    $query->bind_param('s', $id);
    $query->execute() or die('Query failed: ' . $mysqli->error);
}

function set_task_aborted($id)
{
    global $mysqli;
    $query = $mysqli->prepare("UPDATE task SET end_time = CURRENT_TIMESTAMP, status = 'aborted'
WHERE id = ?");
    $query->bind_param('s', $id);
    $query->execute() or die('Query failed: ' . $mysqli->error);
}

function set_task_aborted_if_running($id)
{
    global $running;
    if ($running) {
        set_task_aborted($id);
    }
}

function set_task_saved($id)
{
    global $mysqli;
    $query = $mysqli->prepare("UPDATE task SET status = 'saved' WHERE id = ?");
    $query->bind_param('s', $id);
    $query->execute() or die('Query failed: ' . $mysqli->error);
}

// Get the yearly totals for a given corpus.
function get_totals($corpus)
{
    global $mysqli;
    global $data_start_year;

    $totals = [];
    
    $query = $mysqli->prepare("SELECT SQL_CACHE year, ntokens FROM total
WHERE corpus = ? AND year >= ?");
    $query->bind_param('si', $corpus, $data_start_year);
    $query->execute() or die('Query failed: ' . $mysqli->error);
    $query->bind_result($year, $ntokens);
    while ($query->fetch()) {
        $totals[(int)$year] = (int)$ntokens;
    }
    $query->free_result();
    
    return $totals;
}

// Get the counts for a word in each year.
function get_counts($word, $corpus)
{
    global $mysqli;
    
    $word = mb_strtolower($word, "UTF-8");
    $counts = [];
    $query = $mysqli->prepare("SELECT year, ntokens FROM count
WHERE word = ? AND corpus = ?");
    $query->bind_param('ss', $word, $corpus);
    $query->execute() or die('Query failed: ' . $mysqli->error);
    $query->bind_result($year, $count);
    while ($query->fetch()) {
        $counts[(int)$year] = (int)$count;
    }
    $query->free_result();
    
    return $counts;
}

function parse_year($str)
{
    if ($str == "") {
        return NULL;
    } else {
        return (int)$str;
    }
}

function parse_period($str)
{
    $array = explode('-', $str);
    return array_map("parse_year", $array);
}

// Fetch the usage periods for a word from the database and parse the string.
function get_usage_periods($word, $corpus)
{
    global $mysqli;
    
    $word = strtolower($word);
  
    $query = $mysqli->prepare("SELECT periods FROM usage_periods
WHERE word = ? AND corpus = ?");
    $query->bind_param('ss', $word, $corpus);
    $query->execute() or die('Query failed: ' . $mysqli->error);
    $query->bind_result($periods);

    if ($query->fetch()) {
        $periods = explode(';', $periods);
        $periods = array_map("parse_period", $periods);
    } else {
        $periods = [];
    }
    
    $query->free_result();
  
    return $periods;
}

function load_cache()
{
    global $cachefile;
    global $cache_data;

    global $cache_hits;
    $cache_hits = 0;

    $cache_data = file_get_contents($cachefile) or die('Load cache failed.');
    $cache_data = json_decode($cache_data, true);
}

function get_classes_from_cache($word, $corpus)
{
    global $cache_data;
    if (array_key_exists($word, $cache_data["ngrams"][$corpus])) {
        return $cache_data["ngrams"][$corpus][$word];
    }
    return NULL;
}

function add_classes_to_cache($word, $corpus, $classes)
{
    global $cache_data;
    $cache_data["ngrams"][$corpus][$word] = $classes;
}

function get_dicts_from_cache($word)
{
    global $cache_data;
    if (array_key_exists($word, $cache_data["dict"])) {
      return $cache_data["dict"][$word];
    }
    return NULL;
}

// Gets the usage period classes that will be applied to a given token.
function get_classes_for_word($word)
{
    global $mysqli;
    global $corpus;
    global $cache_hits;

    $word = strtolower($word);

    if (!is_null($classes = get_classes_from_cache($word, $corpus))) {
        $cache_hits += 1;
        return $classes;
    }

    $query = $mysqli->prepare("SELECT SQL_CACHE classes FROM word_classes
WHERE word = ? AND corpus = ?");
    $query->bind_param('ss', $word, $corpus);
    $query->execute() or die('Query failed: ' . $mysqli->error);
    $query->bind_result($classes);

    if (!$query->fetch()) {
        $classes = "";
    }
    
    $query->free_result();
    
    validate_classes($classes);

    //add_classes_to_cache($word, $corpus, $classes);

    return $classes;
}

// Gets a list of dictionaries that contain a given word.
function get_dicts_for_word($word)
{
    global $mysqli;
    global $main_db_name;
    global $wordnet_db_name;
    global $dict_db_name;
    global $dicts;

    if (!is_null($in_dicts = get_dicts_from_cache($word))) {
        return $in_dicts;
    }

    mysqli_select_db($mysqli, $wordnet_db_name) or die('Could not select database');

    $lemmas = lemmatize(strtolower($word), false);
    
    mysqli_select_db($mysqli, $dict_db_name) or die('Could not select database');

    $in_dicts = [];
    $query = $mysqli->prepare("SELECT SQL_CACHE dict FROM dict
WHERE headword = ? AND dict = ?");
    foreach ($lemmas as $lemma) {
        $headword = $lemma[0];
	$query->bind_param('ss', $headword, $dict);
	foreach ($dicts as $dict) {
	    $query->execute() or die('Query failed: ' . $mysqli->error);
	    $query->store_result();
	    if ($query->num_rows > 0) {
	        if (!in_array($dict, $in_dicts)) {
	            $in_dicts[] = $dict;
	        }
	    }
	    $query->free_result();
	}
    }
    $in_dicts = implode("", $in_dicts);
    
    validate_dicts($in_dicts);

    mysqli_select_db($mysqli, $main_db_name) or die('Could not select database');

    return $in_dicts;
}

// Generates the main content of an annotated text, saving it to
// the tmpdir and updating the database accordingly.
function gen_annotated_text($id, $text, $title, $corpus, $offline)
{
  global $max_linelen;
  
  global $cache_hits;
  global $running;
  
  $running = true;
  register_shutdown_function("set_task_aborted_if_running", $id);
  set_task_data($id, $title, $corpus);
  set_task_running($id);
  
  $textlen = strlen($text);
  update_task_total($id, $textlen);
  
  load_cache();

  $content = "<div class='line'>";

  // We will hard wrap the text and place each line in its own <div>.  This is so that
  // it is possible to dynamically modify just those parts of the text that are currently
  // visible in JS.  To get the word-wrapping right, we need to split only on spaces and
  // hypens (so that quotation marks, commas, etc. are grouped with the words to which
  // they are attached).  But we also have apply a second level of tokenization to
  // identify the actual words that should be highlighted.
  $linelen = 0;
  $pos = 0;
  $tok = "";
  $nwords = 0;
  $nlines = 0;
  $nannotations = 0;
  $progress = 0;
  while ($pos < $textlen) {
    
    $newprogress = round($pos / 200);
    if ($newprogress > $progress) {
        $progress = $newprogress;
        
        if (!$offline) {
            // The point of this is to ensure that the server will notice and shut this
            // script down if no one's listening anymore.
            echo ' ';
            flush();
            ob_flush();
            if (connection_aborted() || task_status($id) == 'killed') {
                die();
            }
        }
        
        // Save the task data so that the page can update the progress bar.
        update_task_progress($id, $pos, $nwords, $nlines, $nannotations, $cache_hits);
    }
    
    // These functions are not UTF-8-aware, but it's okay because we're only splitting on
    // single-byte characters and cannot break up a multi-byte sequence this way.  This
    // is for the best because multibyte-aware indexing is inefficient.  UTF-8 becomes
    // important when we deal with punctuation below.
    $toklen = strcspn($text, " \r\n-", $pos);
    $tok = substr($text, $pos, $toklen);
    $delim = substr($text, $pos + $toklen, 1);
    $pos += $toklen + 1;
    
    // Ignore \r before \n.
    if ($delim == "\r") {
        $delim2 = substr($text, $pos, 1);
        if ($delim2 == "\n") {
            $delim = "\n";
            $pos += 1;
        }
    }
    
    // Check whether we've hit the end of the line.
    $linelen += $toklen;
    if ($linelen > $max_linelen) {
      // We are forced to wrap to a new line.
      $content .= "</div>\n<div class='line'>";
      $linelen = $toklen;
      $nlines += 1;
    }
    
    // Deal with the token.  We need to tokenize it again to break it down to actual
    // "words."  In odd cases like "a,b,c" it may actually contain more than one.
    $tokpos = 0;
    // We need to keep separate counters for the byte position and the UTF-8 character
    // position because preg_match is not UTF-8 aware.
    $mb_tokpos = 0;
    while ($tokpos < $toklen) {
      $matches = [];
      if (preg_match('/[\p{L}&]([\p{L}&\'’]*[\p{L}&])?/Su', $tok, $matches, PREG_OFFSET_CAPTURE, $tokpos)
          && $matches[0][1] == $tokpos) {
        // If the RE matches, the next thing in the string is a "real word."
        $word = $matches[0][0];
        $classes = get_classes_for_word($word);
	$dicts = get_dicts_for_word($word);
        if ($classes != "" || $dicts != "") {
          $content .= sprintf("<span data-usage='%s' data-dicts='%s'>%s</span>",
			      $classes, $dicts,
                              htmlspecialchars($word, ENT_QUOTES));
          $nannotations += 1;
        } else {
          $content .= htmlspecialchars($word, ENT_QUOTES);
        }
        $tokpos += strlen($word);
        $mb_tokpos += mb_strlen($word, 'UTF-8');
        $nwords += 1;
      } else {
        $punct = mb_substr($tok, $mb_tokpos, 1, 'UTF-8');
        if ($punct == "_") {
          if ($tokpos > 0) {
            // Add a zero-width space to make sure the browser counts things the actual
            // words in things like _this_ as the units of selection.
            $content .= "&#8203;";
          }
          $content .= "_";
          if ($tokpos < $toklen - 1) {
            $content .= "&#8203;";
          }
          $tokpos += strlen($punct);
          $mb_tokpos += mb_strlen($punct, 'UTF-8');
        } else {
          $content .= htmlspecialchars($punct, ENT_QUOTES);
          $tokpos += strlen($punct);
          $mb_tokpos += mb_strlen($punct, 'UTF-8');
        }
      }
    }
      
    // Deal with the delimiter.
    if ($delim == " ") {
      if ($toklen == 0) {
        // Use &nbsp; for a space that follows some other sort of whitespace.
        $content .= '&nbsp;';
      } else {
        $content .= ' ';
      }
      $linelen += 1;
    } else if ($delim == "\n") {
      if ($linelen == 0) {
        // Insert a &nbsp; for blank lines so that the div doesn't collapse.
        $content .= '&nbsp;';
      }
      $content .= "</div>\n<div class='line'>";
      $linelen = 0;
      $nlines += 1;
    } else {
      $content .= htmlspecialchars($delim, ENT_QUOTES);
      $linelen += 1;
    }
  }

  if ($linelen == 0) {
    $content .= '&nbsp;';
  }
  $content .= "</div>\n";
  
  update_task_progress($id, $textlen, $nwords, $nlines, $nannotations, $cache_hits);
  
  save_text_to_tmp_file($id, $content, $title, $corpus);
  
  set_task_completed($id);
  $running = false;
}

// Strips out all the annotations/HTML character refs added by the above.
function strip_annotations($content)
{
  $text = str_replace("<div class='line'>&nbsp;</div>", "", $content);
  $text = str_replace("&nbsp;", " ", $text);
  $text = str_replace("&#8203;", "", $text);
  $text = strip_tags($text);
  $text = htmlspecialchars_decode($text, ENT_QUOTES);
  
  return $text;
}

function acquire_storage_lock()
{
  global $lockfile;
  $handle = fopen($lockfile, 'w');
  flock($handle, LOCK_EX);
  fwrite($handle, getmypid());
  fflush($handle);
  return $handle;
}

function release_storage_lock($handle)
{
  global $lockfile;
  flock($handle, LOCK_UN);
  fclose($handle);
  unlink($lockfile);
}

function generate_id($text)
{
  $str = date(DATE_COOKIE) . substr($text, 0, 256);
  $md5 = md5($str, true);
  $base64 = base64_encode($md5);
  $id = substr(strtr(rtrim($base64, '='), '+/', 'ab'), 0, 8);

  // We need to lock in case another process creates/moves a file inbetween when we
  // pick the id and when we touch the file.
  $handle = acquire_storage_lock();

  if (is_id_registered($id)) {
    $i = 0;
    while (is_id_registered($id . (string)$i)) {
      $i += 1;
    }
    $id = $id . (string)$i;
  }
  create_task_entry($id);
  
  release_storage_lock($handle);

  return $id;
}

function get_tmp_filename_from_id($id)
{
  global $tmpdir;
  validate_id($id);
  return $tmpdir . $id;
}

function write_text_to_file($filename, $content, $title, $corpus)
{
  $data = ["content" => $content, "title" => $title, "corpus" => $corpus];
  $data = json_encode($data);
  $data = gzcompress($data);
  
  $handle = acquire_storage_lock();
  file_put_contents($filename, $data) or die('Save text failed.');
  release_storage_lock($handle);
}

// Save a text to the tmpdir so that it can be accessed later.
function save_text_to_tmp_file($id, $content, $title, $corpus)
{
  $filename = get_tmp_filename_from_id($id);

  write_text_to_file($filename, $content, $title, $corpus);

  return $id;
}

// Touch a tmp file to keep it from getting deleted.
function touch_tmp_file($id)
{
    $filename = get_tmp_filename_from_id($id);
    if (file_exists($filename)) {
        touch($filename) or die('Touch file failed.');
    }
}

function get_text_from_tmp_file($id)
{
  $filename = get_tmp_filename_from_id($id);

  $handle = acquire_storage_lock();
  $data = file_get_contents($filename) or die('Load text failed.');
  release_storage_lock($handle);
  
  $data = gzuncompress($data);
  $data = json_decode($data, true);
  
  return $data;
}

function get_filename_from_id($id)
{
  global $savedir;
  validate_id($id);
  return $savedir . $id;
}

function is_text_completed_and_unsaved($id)
{
    global $mysqli;
    $query = $mysqli->prepare("SELECT status FROM task WHERE id = ?");
    $query->bind_param('s', $id);
    $query->execute() or die('Query failed: ' . $mysqli->error);
    $query->bind_result($status);
    if ($query->fetch()) {
        return $status == "completed";
    } else {
        return false;
    }
}

function is_text_saved($id)
{
    global $mysqli;
    $query = $mysqli->prepare("SELECT status FROM task WHERE id = ?");
    $query->bind_param('s', $id);
    $query->execute() or die('Query failed: ' . $mysqli->error);
    $query->bind_result($status);
    if ($query->fetch()) {
        return $status == "saved";
    } else {
        return false;
    }
}

// Move a text from the tmpdir to the savedir.
function save_text($id)
{
  $tmp_filename = get_tmp_filename_from_id($id);
  $filename = get_filename_from_id($id);

  $handle = acquire_storage_lock();
  rename($tmp_filename, $filename) or die('Save text failed.');
  release_storage_lock($handle);
  
  set_task_saved($id);
}

// Get the JSON data for a text from the savedir.
function get_saved_text($id)
{
  $filename = get_filename_from_id($id);

  $handle = acquire_storage_lock();
  $data = file_get_contents($filename) or die('Load text failed.');
  release_storage_lock($handle);
  
  $data = gzuncompress($data);
  $data = json_decode($data, true);
  
  return $data;
}

function is_lemma($word)
{
    global $mysqli;
    $query = $mysqli->prepare("
SELECT word.lemma
FROM word
WHERE word.lemma = ?
");
    $query->bind_param('s', $word);
    if ($query->execute()) {
        $query->store_result();
        return (bool)($query->num_rows > 0);
    } else {
        return false;
    }
}

// Used in the below.
function check_detachment(&$lemmas, $word, $suffix, $ending, $pos)
{
    $length = strlen($suffix);
    if (substr($word, -$length) == $suffix) {
        $s = substr($word, 0, -$length) . $ending;
        if (is_lemma($s)) {
            $pair = [$s, $pos];
            // Before adding the lemma to the list, check whether it's a duplicate.
            // Important note: we are not accounting for the use of "nv" to
            // indicate either a noun or a verb here.  This will produce duplicate
            // results if there are cases where a lemma produced by "nv" rules is
            // also produced by another rule that specifies either "n" or "v".  This
            // could be changed without too much hassle, but it's not needed at
            // present because this case never occurs in our rules.
            if (!in_array($pair, $lemmas)
                && !in_array([$s, null], $lemmas)) {
                $lemmas[] = $pair;
            }
        }
    }
}

// A less-intelligent imitation of WordNet's Morphy algorithm.  Converts an inflected
// word (e.g. "thought") into the primary form of the lemma ("think").  Returns a list
// of pairs [lemma, pos] where pos (possibly empty) is an inferred part of speech
// indicator.  In addition to the WordNet pos strings, the pos can be "nv" indicating
// that the word is either a noun or a verb.  If $check_wordnet is true, only includes
// the word itself if it is in WordNet
function lemmatize($word, $check_wordnet)
{
    global $mysqli;
    
    $lemmas = [];
    
    if ($word == "") {
        return $lemmas;
    }
    
    // First: check if the word is the primary form of a lemma.
    if (!$check_wordnet || is_lemma($word)) {
        $lemmas[] = [$word, null];
    }
    
    // Second: check the exceptions list.
    $query = $mysqli->prepare("
SELECT word.lemma, morphref.pos
FROM morphdef
INNER JOIN morphref ON (morphdef.morphid = morphref.morphid)
INNER JOIN word ON (morphref.wordid = word.wordid)
WHERE morphdef.lemma = ?
");
    $query->bind_param('s', $word);
    if ($query->execute()) {
        $query->bind_result($lemma, $pos);
        while ($query->fetch()) {
            // We are assuming that the result cannot be the same as the original word.
            $lemmas[] = [$lemma, $pos];
        }
    }
    
    // Third: try a sequence of formulaic substitutions.
    check_detachment($lemmas, $word, "s", "", "nv");
    check_detachment($lemmas, $word, "ies", "y", "nv");
    check_detachment($lemmas, $word, "'s", "", "n");
    check_detachment($lemmas, $word, "ses", "s", "n");
    check_detachment($lemmas, $word, "xes", "x", "n");
    check_detachment($lemmas, $word, "zes", "z", "n");
    check_detachment($lemmas, $word, "ches", "ch", "n");
    check_detachment($lemmas, $word, "shes", "sh", "n");
    check_detachment($lemmas, $word, "men", "man", "n");
    check_detachment($lemmas, $word, "es", "", "v");
    check_detachment($lemmas, $word, "ed", "e", "v");
    check_detachment($lemmas, $word, "ed", "", "v");
    check_detachment($lemmas, $word, "ing", "e", "v");
    check_detachment($lemmas, $word, "ing", "", "v");
    check_detachment($lemmas, $word, "er", "", "a");
    check_detachment($lemmas, $word, "est", "", "a");
    check_detachment($lemmas, $word, "er", "e", "a");
    check_detachment($lemmas, $word, "est", "e", "a");
    
    return $lemmas;
}

// Get a list of definitions from the wordnet30 database.
function get_wordnet_definitions($word)
{
    global $mysqli;
    
    $definitions = [];

    $lemmas = lemmatize($word, true);
    foreach ($lemmas as $pair) {
        $lemma = $pair[0];
        $pos = $pair[1];
        if ($pos == "nv") {
            $pos_predicate = "AND synset.pos IN ('n', 'v')";
        } else if ($pos) {
            $pos_predicate = "AND synset.pos = ?";
        } else {
            $pos_predicate = "";
        }
        $qstring = sprintf("
SELECT coalesce(casedword.lemma, word.lemma), synset.pos, sense.rank,
    group_concat(coalesce(casedword2.lemma, word2.lemma) ORDER BY sense2.rank SEPARATOR ';') as synonyms,
    synset.definition
FROM word
INNER JOIN sense ON (word.wordid = sense.wordid)
LEFT JOIN casedword ON (sense.casedwordid = casedword.wordid)
INNER JOIN synset ON (sense.synsetid = synset.synsetid)
INNER JOIN sense AS sense2 ON (synset.synsetid = sense2.synsetid)
INNER JOIN word AS word2 ON (sense2.wordid = word2.wordid)
LEFT JOIN casedword AS casedword2 ON (sense2.casedwordid = casedword2.wordid)
WHERE word.lemma = ? %s
GROUP BY synset.pos, sense.rank, synset.definition
", $pos_predicate);
        $query = $mysqli->prepare($qstring);
        if ($pos && $pos != "nv") {
            $query->bind_param('ss', $lemma, $pos);
        } else {
            $query->bind_param('s', $lemma);
        }
        if ($query->execute()) {
            $query->bind_result($lemma, $pos, $rank, $syns, $def);
            while ($query->fetch()) {
                $definitions[] = [$lemma, $pos, (int)$rank, $syns, $def];
            }
            $query->free_result();
        }
    }
    
    return $definitions;
}

// Get a definition from Webster's 1828 dictionary.
function get_dict_definitions($word)
{
    global $mysqli, $dict_db_name, $wordnet_db_name;
    global $dicts;
    
    if (count($dicts) == 0) {
        return;
    }

    // Lemmatize and discard the POS info.
    $word = strtolower($word);
    $lemmas = lemmatize($word, false);
    $lemmas2 = [];
    foreach ($lemmas as $lemma) {
        $lemmas2[] = $lemma[0];
    }
    
    mysqli_select_db($mysqli, $dict_db_name) or die('Could not select database');
    
    $definitions = array();
    
    foreach ($dicts as $dict) {
        $deflist = [];
    
        foreach ($lemmas2 as $pair) {
            $lemma = $pair;
            $query = $mysqli->prepare("
SELECT entry
FROM dict
WHERE dict.dict = ? AND dict.headword = ?
");
            $query->bind_param('ss', $dict, $lemma);
            if ($query->execute()) {
                $query->bind_result($entry);
                while ($query->fetch()) {
                    $deflist[] = [$entry];
                }
                $query->free_result();
            }
        }
        
        $definitions[$dict] = $deflist;
    }
    
    mysqli_select_db($mysqli, $wordnet_db_name) or die('Could not select database');
    
    return $definitions;
}

?>
