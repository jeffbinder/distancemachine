<?php

include 'config.php';

function load_stopwords()
{
    global $mysqli;
    global $stopwords;
    if ($stopwords) {
        return;
    }
    
    $query = $mysqli->prepare("SELECT * FROM INFORMATION_SCHEMA.INNODB_FT_DEFAULT_STOPWORD");
    $query->execute() or die('Query failed: ' . $mysqli->error);
    $query->bind_result($word);
    
    $stopwords = [];
    while ($query->fetch()) {
        $stopwords[] = $word;
    }
}

function is_stopword($word)
{
    global $stopwords;
    load_stopwords();
    return in_array($word, $stopwords);
}

function validate_id($id)
{
    if (!(is_string($id) && preg_match('/^[A-Za-z0-9]+$/', $id) && strlen($id) <= 9
          && is_id_registered($id))) {
        die("This text could not be found.");
    }
}

function validate_year($year, $corpus)
{
    global $start_year, $end_year;
    if (!(is_int($year) && $year >= $start_year[$corpus] && $year <= $end_year[$corpus])) {
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
        || preg_match('/[\x00-\x08\x0B\x0C\x0E-\x1F]/u', $text)) {
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

function validate_usage_notes($dicts)
{
    if (!(is_string($dicts))) {
        die("Invalid usage notes!");
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

function reset_task_start_time($id)
{
    global $mysqli;
    $query = $mysqli->prepare("UPDATE task SET start_time = NOW() WHERE id = ?");
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

// Get the word count for a text.
function get_word_count($id)
{
    global $mysqli;
    $query = $mysqli->prepare("SELECT words_completed FROM task WHERE id = ?");
    $query->bind_param('s', $id);
    $query->execute() or die('Query failed: ' . $mysqli->error);
    $query->bind_result($nwords);
    $query->fetch();
    return $nwords;
}

// Get the yearly totals for a given corpus.
function get_totals($corpus)
{
    global $mysqli;
    global $data_start_year;

    $totals = [];
    
    $query = $mysqli->prepare("SELECT SQL_CACHE year, ntokens FROM total
WHERE corpus = ? AND year >= ?");
    $query->bind_param('si', $corpus, $data_start_year[$corpus]);
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
    global $data_start_year;
    
    $word = mb_strtolower($word, "UTF-8");
    $counts = [];
    $query = $mysqli->prepare("SELECT year, ntokens FROM count
WHERE word = ? AND corpus = ? AND year >= ?");
    $query->bind_param('ssi', $word, $corpus, $data_start_year[$corpus]);
    $query->execute() or die('Query failed: ' . $mysqli->error);
    $query->bind_result($year, $count);
    while ($query->fetch()) {
        $counts[(int)$year] = (int)$count;
    }
    $query->free_result();
    
    return $counts;
}

// Get the counts for a word in each decade.
function get_decade_counts($word, $corpus)
{
    global $mysqli;
    global $data_start_year;
    
    $word = mb_strtolower($word, "UTF-8");
    $counts = [];
    $query = $mysqli->prepare("SELECT floor(year / 10) * 10, sum(ntokens)
FROM count
WHERE word = ? AND corpus = ? AND year >= ?
GROUP BY floor(year / 10) * 10");
    $query->bind_param('ssi', $word, $corpus, $data_start_year[$corpus]);
    $query->execute() or die('Query failed: ' . $mysqli->error);
    $query->bind_result($year, $count);
    while ($query->fetch()) {
        $counts[(int)$year] = (int)$count;
    }
    $query->free_result();
    
    return $counts;
}

function get_total_count_for_word($word, $corpus)
{
    global $mysqli;
    global $data_start_year;
    
    $word = mb_strtolower($word, "UTF-8");
    $query = $mysqli->prepare("SELECT SQL_CACHE ntokens FROM total_count
WHERE word = ? AND corpus = ?");
    $query->bind_param('ss', $word, $corpus);
    $query->execute() or die('Query failed: ' . $mysqli->error);
    $query->bind_result($count);

    if (!$query->fetch()) {
        $count = 0;
    }
    
    $query->free_result();
    
    return (int)$count;
}

$common_word_lists = [];
function load_common_word_list($corpus, $above_freq)
{
    global $common_word_lists;
    global $mysqli;
    global $data_start_year;
    
    $query = $mysqli->prepare("SELECT word FROM total_count
WHERE corpus = ? AND ntokens > ?");
    $query->bind_param('si', $corpus, $above_freq);
    $query->execute() or die('Query failed: ' . $mysqli->error);
    $query->bind_result($word);

    $words = [];
    while ($query->fetch()) {
        $words[$word] = 1;
    }
    
    $query->free_result();
    
    $common_word_lists[$corpus . $above_freq] = $words;
}

function is_word_common($word, $corpus, $above_freq)
{
    global $common_word_lists;
    if (array_key_exists($corpus . $above_freq, $common_word_lists)) {
        return array_key_exists($word, $common_word_lists[$corpus . $above_freq]);
    } else {
        return get_total_count_for_word($word, $corpus) > $above_freq;
    }
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

function get_freq_from_cache($word)
{
    global $cache_data;
    if (array_key_exists($word, $cache_data["freq"])) {
      return $cache_data["freq"][$word];
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

    if (!is_null($omitted_dicts = get_dicts_from_cache($word))) {
        return $omitted_dicts;
    }

    mysqli_select_db($mysqli, $wordnet_db_name) or die('Could not select database');

    $lemmas = lemmatize(strtolower($word), false);
    
    mysqli_select_db($mysqli, $dict_db_name) or die('Could not select database');

    $in_dicts = [];
    $lemmas_in_dict = [];
    foreach ($dicts as $dict) {
        $lemmas_in_dict[$dict] = [];
    }
    $query = $mysqli->prepare("SELECT SQL_CACHE pos FROM dict
WHERE headword = ? AND dict = ?");
    foreach ($lemmas as $lemma) {
        $headword = $lemma[0];
        $possible_pos = $lemma[1];
        $query->bind_param('ss', $headword, $dict);
        foreach ($dicts as $dict) {
            $query->execute() or die('Query failed: ' . $mysqli->error);
            $query->bind_result($pos);
            while ($query->fetch()) {
                if (!check_pos($possible_pos, $pos)) {
                    continue;
                }
                if (!in_array($dict, $in_dicts)) {
                    $in_dicts[] = $dict;
                }
                if (!in_array($headword, $lemmas_in_dict)) {
                    $lemmas_in_dict[$dict][] = $headword;
                }
            }
            $query->free_result();
        }
    }
    
    $omitted_dicts = [];
    foreach ($dicts as $dict) {
        if (!in_array($dict, $in_dicts)) {
            $omitted_dicts[] = 'x' . $dict;
        }
    }
    $omitted_dicts = implode("", $omitted_dicts);
    
    validate_dicts($omitted_dicts);

    $usage_notes = [];
    $query = $mysqli->prepare("SELECT SQL_CACHE note_class FROM dict_usage_notes
WHERE headword = ? AND dict = ?");
    foreach ($dicts as $dict) {
        $dict_usage_notes = [];
        $first_lemma = true;
        foreach ($lemmas as $lemma) {
            $headword = $lemma[0];
                if (!in_array($headword, $lemmas_in_dict[$dict])) {
                    continue;
                }
            $lemma_usage_notes = [];
            $query->bind_param('ss', $headword, $dict);
            $query->execute() or die('Query failed: ' . $mysqli->error);
            $query->bind_result($note_class);
            while ($query->fetch()) {
                $usage_note = $note_class . $dict;
                $lemma_usage_notes[] = $usage_note;
            }
            $query->free_result();
            if ($first_lemma) {
                $dict_usage_notes = $lemma_usage_notes;
                $first_lemma = false;
            } else {
                // Usage notes require a unanimous vote from all possible lemmas.
                $dict_usage_notes = array_intersect($dict_usage_notes, $lemma_usage_notes);
            }
        }
        $usage_notes = array_merge($usage_notes, $dict_usage_notes);
    }
    $usage_notes = implode("", $usage_notes);
    
    validate_usage_notes($usage_notes);

    mysqli_select_db($mysqli, $main_db_name) or die('Could not select database');

    return $omitted_dicts . $usage_notes;
}

// Gets (the reciprocal of) the usage frequency for a word.
function get_freq_for_word($word)
{
    global $mysqli;
    global $corpus;
    global $cache_hits;

    $word = strtolower($word);

    if (!is_null($freq = get_freq_from_cache($word, $corpus))) {
        return $freq;
    }

    $query = $mysqli->prepare("SELECT SQL_CACHE mean_frequency FROM usage_periods
WHERE word = ? AND corpus = ?");
    $query->bind_param('ss', $word, $corpus);
    $query->execute() or die('Query failed: ' . $mysqli->error);
    $query->bind_result($freq);

    if (!$query->fetch()) {
        $freq = 0.0;
    }
    
    $query->free_result();

    return $freq;
}

// Generates the main content of an annotated text, saving it to
// the tmpdir and updating the database accordingly.  If $id is null,
// returns the text rather than saving it.
function gen_annotated_text($id, $text, $title, $corpus, $offline, $count_word_types,
                            $count_words_above_freq)
{
  global $max_linelen;
  global $max_freq;
  
  global $cache_hits;
  global $running;
  
  $running = true;
  if ($id) {
    register_shutdown_function("set_task_aborted_if_running", $id);
    set_task_data($id, $title, $corpus);
    set_task_running($id);
  }

  if ($title != "Untitled") {
    $text = $title . "\n\n" . $text;
  }
  
  $textlen = strlen($text);
  if ($id) {
    update_task_total($id, $textlen);
  }
  
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
  $nwords_above_freq = 0;
  if ($count_word_types) {
    $word_types = [];
    $word_types_above_freq = [];
  }
  $nlines = 0;
  $nannotations = 0;
  $progress = 0;
  while ($pos < $textlen) {
    
    $newprogress = round($pos / 200);
    if ($newprogress > $progress) {
        $progress = $newprogress;
        
        if ($id && !$offline) {
            // The point of this is to ensure that the server will notice and shut this
            // script down if no one's listening anymore.
            echo ' ';
            flush();
            ob_flush();
            if (connection_aborted() || task_status($id) == 'killed') {
                die();
            }
        }
        
        if ($id) {
          // Save the task data so that the page can update the progress bar.
          update_task_progress($id, $pos, $nwords, $nlines, $nannotations, $cache_hits);
        } else {
          echo $pos . " / " . $textlen . "\n";
        }
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
        $word_quotes_normalized = str_replace("’", "'", $word);
        $classes = get_classes_for_word($word_quotes_normalized);
        $dicts = get_dicts_for_word($word_quotes_normalized);
        $freq = get_freq_for_word($word_quotes_normalized);
        if ($classes != "" || $dicts != "" || $freq <= 1.0 / $max_freq) {
            if ($freq == 0) {
                $inv_freq = 0;
            } else {
                $inv_freq = round(1.0 / $freq);
            }
          $content .= sprintf("<span data-usage='%s' data-dicts='%s' data-freq='%s'>%s</span>",
                              $classes, $dicts, $inv_freq,
                              htmlspecialchars($word, ENT_QUOTES));
          $nannotations += 1;
        } else {
          $content .= htmlspecialchars($word, ENT_QUOTES);
        }
        $tokpos += strlen($word);
        $mb_tokpos += mb_strlen($word, 'UTF-8');
        $nwords += 1;
	$word_lower = strtolower($word_quotes_normalized);
	if ($count_word_types) {
	  $word_types[$word_lower] = 1;
	}
        if ($count_words_above_freq) {
	  if (is_word_common($word_lower, $corpus, $count_words_above_freq)) {
            $nwords_above_freq += 1;
	    if ($count_word_types) {
	      $word_types_above_freq[$word_lower] = 1;
	    }
	  }
        }
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
    } else if ($delim == "-" && $linelen == $max_linelen) {
      // Hyphens at the end of a line are free.
      $content .= htmlspecialchars($delim, ENT_QUOTES);
    } else {
      $content .= htmlspecialchars($delim, ENT_QUOTES);
      $linelen += 1;
    }
  }

  if ($linelen == 0) {
    $content .= '&nbsp;';
  }
  $content .= "</div>\n";
  
  if ($id) {
    update_task_progress($id, $textlen, $nwords, $nlines, $nannotations, $cache_hits);
    save_text_to_tmp_file($id, $content, $title, $corpus);
    set_task_completed($id);
  }
  $running = false;

  if (!$id) {
      $ret = ['content' => $content, 'word_count' => $nwords];
      if ($count_word_types) {
          $ret['word_types'] = count($word_types);
      }
      if ($count_words_above_freq) {
          $ret['words_above_freq'] = $nwords_above_freq;
	  if ($count_word_types) {
	    $ret['word_types_above_freq'] = count($word_types_above_freq);
	  }
      }
      return $ret;
  }
}

// Strips out all the annotations/HTML character refs added by the above.
function strip_annotations($content)
{
  $text = str_replace("<div class='line'>&nbsp;</div>", "", $content);
  $text = str_replace("&nbsp;", " ", $text);
  $text = str_replace("&#8203;", "", $text);
  $text = strip_tags($text);
  $text = str_replace(" \n", "\n", $text);
  $text = htmlspecialchars_decode($text, ENT_QUOTES);
  
  return $text;
}

// Computes statistics about a text that has been annotated by gen_annotated_text.
function compute_text_stats($content, $word_count, $word_types, $corpus, $above_freq)
{
  global $start_year;
  global $end_year;
  global $dicts;
  
  $nyears = $end_year[$corpus] - $start_year[$corpus] + 1;

  $stats = [
	    "ctyp" => array_pad([], $nyears, 0),
	    "otyp" => array_pad([], $nyears, 0),
	    "ntyp" => array_pad([], $nyears, 0),
	    "ltyp" => array_pad([], $nyears, 0),
	    "ctok" => array_pad([], $nyears, 0),
	    "otok" => array_pad([], $nyears, 0),
	    "ntok" => array_pad([], $nyears, 0),
	    "ltok" => array_pad([], $nyears, 0)
	    ];

  $dict_stats = [
		 "x" => [],
		 "o" => [],
		 "v" => []
		 ];
  foreach ($dicts as $dict) {
    $dict_stats["x"][$dict] = 0;
    $dict_stats["o"][$dict] = 0;
    $dict_stats["v"][$dict] = 0;
  }
    
  $freq_stats = ["l" => [1000000 => 0, 10000000 => 0, 100000000 => 0,
			 1000000000 => 0, 10000000000 => 0], "a" => 0];

  $words = [
	    "c" => array_pad([], $nyears, []),
	    "o" => array_pad([], $nyears, []),
	    "n" => array_pad([], $nyears, []),
	    "l" => array_pad([], $nyears, [])
	    ];
  $re = '/data-usage=\'([^\']+)\'[^>]*>([^<]*)</u';
  preg_match_all($re, $content, $matches, PREG_SET_ORDER);
  foreach ($matches as $match) {
    $usage = $match[1];
    $word = strtolower($match[2]);
    $word = str_replace("’", "'", $word);
    if (!is_word_common($word, $corpus, $above_freq)) {
      continue;
    }
    $len = strlen($usage);
    for ($k = 0; $k < $len; $k += 5) {
      $usage_state = substr($usage, $k, 1);
      $ch = substr($usage, $k+3, 1);
      if ($ch == "x") {
	$cent = intval(substr($usage, $k+1, 2)) * 100;
	$idx = $cent - $start_year[$corpus];
	for ($a = 0; $a < 100; $a++) {
	  $words[$usage_state][$idx + $a][] = $word;
	}
      } else if ($ch == "l") {
	$cent = intval(substr($usage, $k+1, 2)) * 100;
	$idx = $cent - $start_year[$corpus];
	for ($a = 0; $a < 50; $a++) {
	  $words[$usage_state][$idx + $a][] = $word;
	}
      } else if ($ch == "r") {
	$cent = intval(substr($usage, $k+1, 2)) * 100;
	$idx = $cent - $start_year[$corpus];
	for ($a = 50; $a < 100; $a++) {
	  $words[$usage_state][$idx + $a][] = $word;
	}
      } else {
	$ch = substr($usage, $k+4, 1);
	if ($ch == "x") {
	  $dec = intval(substr($usage, $k+1, 3)) * 10;
	  $idx = $dec - $start_year[$corpus];
	  for ($a = 0; $a < 10; $a++) {
	    $words[$usage_state][$idx + $a][] = $word;
	  }
	} else if ($ch == "l") {
	  $dec = intval(substr($usage, $k+1, 3)) * 10;
	  $idx = $dec - $start_year[$corpus];
	  for ($a = 0; $a < 5; $a++) {
	    $words[$usage_state][$idx + $a][] = $word;
	  }
	} else if ($ch == "r") {
	  $dec = intval(substr($usage, $k+1, 3)) * 10;
	  $idx = $dec - $start_year[$corpus];
	  for ($a = 5; $a < 10; $a++) {
	    $words[$usage_state][$idx + $a][] = $word;
	  }
	} else {
	  $y = intval(substr($usage, $k+1, 4));
	  $idx = $y - $start_year[$corpus];
	  $words[$usage_state][$idx][] = $word;
	}
      }
    }
  }
  for ($i = 0; $i < $nyears; $i++) {
    $stats["otyp"][$i] = count(array_unique($words["o"][$i]));
    $stats["ntyp"][$i] = count(array_unique($words["n"][$i]));
    $stats["ltyp"][$i] = count(array_unique($words["l"][$i]));
    $stats["otok"][$i] = count($words["o"][$i]);
    $stats["ntok"][$i] = count($words["n"][$i]);
    $stats["ltok"][$i] = count($words["l"][$i]);
  }
  
  $re = '/data-dicts=\'([^\']+)\'[^>]*>([^<]*)</u';
  preg_match_all($re, $content, $matches, PREG_SET_ORDER);
  foreach ($matches as $match) {
    $dict_list = $match[1];
    $word = strtolower($match[2]);
    $word = str_replace("’", "'", $word);
    if (!is_word_common($word, $corpus, $above_freq)) {
      continue;
    }
    foreach ($dicts as $dict) {
      if (strpos($dict_list, "x" . $dict) != FALSE) {
	$dict_stats["x"][$dict] += 1;
      }
      if (strpos($dict_list, "o" . $dict) != FALSE) {
	$dict_stats["o"][$dict] += 1;
      }
      if (strpos($dict_list, "v" . $dict) != FALSE) {
	$dict_stats["v"][$dict] += 1;
      }
    }
  }
  
  $re = '/data-freq=\'([^\']+)\'[^>]*>([^<]*)</u';
  preg_match_all($re, $content, $matches, PREG_SET_ORDER);
  foreach ($matches as $match) {
    $freq = intval($match[1]);
    $word = strtolower($match[2]);
    $word = str_replace("’", "'", $word);
    if (!is_word_common($word, $corpus, $above_freq)) {
      continue;
    }
    if ($freq == 0) {
      $freq_stats["a"] += 1;
    } else {
      for ($k = 10000000000; $k >= 1000000; $k *= 0.1) {
	if ($freq <= $k) {
	  break;
	}
	$freq_stats["l"][$k] += 1;
      }
    }
    }

  $tok_scale = 100.0 / $word_count;
  $typ_scale = 100.0 / $word_types;
  for ($i = 0; $i < $nyears; $i++) {
    $stats["ctok"][$i] = ($word_count - $stats["otok"][$i]
			  - $stats["ntok"][$i] - $stats["ltok"][$i]);
    $stats["ctok"][$i] *= $tok_scale;
    $stats["otok"][$i] *= $tok_scale;
    $stats["ntok"][$i] *= $tok_scale;
    $stats["ltok"][$i] *= $tok_scale;
    $stats["ctyp"][$i] = ($word_types - $stats["otyp"][$i]
			  - $stats["ntyp"][$i] - $stats["ltyp"][$i]);
    $stats["ctyp"][$i] *= $typ_scale;
    $stats["otyp"][$i] *= $typ_scale;
    $stats["ntyp"][$i] *= $typ_scale;
    $stats["ltyp"][$i] *= $typ_scale;
  }
  foreach ($dicts as $dict) {
    $dict_stats["x"][$dict] *= $tok_scale;
    $dict_stats["o"][$dict] *= $tok_scale;
    $dict_stats["v"][$dict] *= $tok_scale;
  }
  for ($i = 10000000000; $i >= 1000000; $i *= 0.1) {
    $freq_stats["l"][$i] *= $tok_scale;
  }
  $freq_stats["a"] *= $tok_scale;

  return ['usage_stats' => $stats, 'dict_stats' => $dict_stats,
	  'freq_stats' => $freq_stats];
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

function log_word_lookup($word)
{
    global $mysqli;
    $query = $mysqli->prepare("INSERT INTO word_lookup_log (word)
VALUES (?)");
    $query->bind_param('s', $word);
    $query->execute() or die('Query failed: ' . $mysqli->error);
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

// Check whether two units of part-of-speech info are compatible.
function check_pos($pos1, $pos2)
{
    if ($pos1 == '' || $pos2 == '') {
        // One of the POS lists is empty, meaning nothing is known.
        return true;
    }
    return count(array_intersect(str_split($pos1), str_split($pos2))) > 0;
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
GROUP BY coalesce(casedword.lemma, word.lemma), synset.pos, sense.rank, synset.definition
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

// Get a definition from an old dictionary.
function get_dict_definitions($word)
{
    global $mysqli, $dict_db_name, $wordnet_db_name;
    global $dicts;
    
    if (count($dicts) == 0) {
        return;
    }

    $word = strtolower($word);
    $lemmas = lemmatize($word, false);
    
    mysqli_select_db($mysqli, $dict_db_name) or die('Could not select database');
    
    $definitions = array();
    
    foreach ($dicts as $dict) {
        $deflist = [];
        
        $headwords_used = [];
        foreach ($lemmas as $lemma) {
            if (in_array($lemma[0], $headwords_used)) {
                continue;
            }
            $query = $mysqli->prepare("
SELECT entry, pos
FROM dict
WHERE dict.dict = ? AND dict.headword = ?
");
            $query->bind_param('ss', $dict, $lemma[0]);
            if ($query->execute()) {
                $query->bind_result($entry, $pos);
                while ($query->fetch()) {
                    if (check_pos($pos, $lemma[1])) {
                        $deflist[] = [$entry];
                        $headwords_used[] = $lemma[0];
                    }
                }
                $query->free_result();
            }
        }
        
        $definitions[$dict] = $deflist;
    }
    
    mysqli_select_db($mysqli, $wordnet_db_name) or die('Could not select database');
    
    return $definitions;
}

// Find dictionary definitions that use the specified word
function reverse_dict_lookup($word, $dict)
{
    global $mysqli, $dict_db_name, $wordnet_db_name;

    $word = strtolower($word);
    $lemmas = lemmatize($word, false);
    
    mysqli_select_db($mysqli, $dict_db_name) or die('Could not select database');
    
    $headwords = [];
    $lemmas_used = [];
    foreach ($lemmas as $lemma) {
        if (in_array($lemma[0], $lemmas_used)) {
            continue;
        }
        $query = $mysqli->prepare("
SELECT headword
FROM dict_index
WHERE dict_index.dict = ? AND dict_index.lemma = ?
");
        $query->bind_param('ss', $dict, $lemma[0]);
        if ($query->execute()) {
            $query->bind_result($headword);
            while ($query->fetch()) {
                $headwords[] = $headword;
            }
            $query->free_result();
        }
    }
    
    mysqli_select_db($mysqli, $wordnet_db_name) or die('Could not select database');
    
    return array_values(array_unique($headwords));
}

// Functions for archival mode

function check_archive_mode()
{
  global $archive_mode;
  if (!$archive_mode) {
        die("Archive mode is not enabled!");
    }
}

function validate_archive_uri($corpus, $uri)
{
    if (!(is_string($corpus) && preg_match('/^[A-Za-z0-9]+$/', $corpus) && strlen($corpus) <= 255
          && is_string($uri) && preg_match('/^[A-Za-z0-9]+$/', $uri) && strlen($uri) <= 255
          && is_uri_registered($corpus, $uri))) {
        die("This text could not be found.");
    }
}

function validate_search_string($q)
{
  if (!(is_string($q) && strlen($q) <= 1000)) {
        die("Invalid search query.");
    }
}

function is_uri_registered($corpus, $uri)
{
    global $mysqli;
    $query = $mysqli->prepare("SELECT uri FROM text WHERE corpus = ? AND uri = ?");
    $query->bind_param('ss', $corpus, $uri);
    $query->execute() or die('Query failed: ' . $mysqli->error);
    $query->store_result();
    return (bool)($query->num_rows > 0);
}

function create_text_entry($corpus, $uri, $title, $author, $year, $word_count, $text)
{
    global $mysqli;
    // Unfortunately MySQL doesn't support 4-byte unicode characters, so we strip them out.
    $text = preg_replace('/[\xF0-\xF7].../s', '', $text);
    $query = $mysqli->prepare("INSERT INTO text
(corpus, uri, title, author, pub_year, word_count, text)
VALUES (?, ?, ?, ?, ?, ?, ?)");
    $query->bind_param('ssssdss', $corpus, $uri, $title, $author, $year, $word_count, $text);
    $query->execute() or die('Query failed: ' . $mysqli->error);
}

function get_text_metadata($corpus, $uri)
{
    global $mysqli;
    $query = $mysqli->prepare("SELECT title, author, pub_year FROM text WHERE corpus = ? AND uri = ?");
    $query->bind_param('ss', $corpus, $uri);
    $query->execute() or die('Query failed: ' . $mysqli->error);
    $query->bind_result($title, $author, $pub_year);
    $query->fetch();
    return ['title' => $title, 'author' => $author, 'pub_year' => $pub_year];
}

function get_text_content($corpus, $uri)
{
    global $archive_dir;
    $content = file_get_contents($archive_dir . $corpus . "/" . $uri) or die('Load text failed.');
    return $content;
}

function get_text_word_count($corpus, $uri)
{
    global $mysqli;
    $query = $mysqli->prepare("SELECT word_count FROM text WHERE corpus = ? AND uri = ?");
    $query->bind_param('ss', $corpus, $uri);
    $query->execute() or die('Query failed: ' . $mysqli->error);
    $query->bind_result($word_count);
    $query->fetch();
    return $word_count;
}

function tokenize_query($q)
{
  preg_match_all('/\w+|"(?:\\"|[^"])+"/', $q, $results);
  return $results[0];
}

function fulltext_search($corpus, $q, $ymin, $ymax)
{
  global $mysqli;

  $query = $mysqli->prepare("SELECT uri, title, author, pub_year
FROM text
WHERE corpus = ? AND pub_year >= ? AND pub_year <= ?
      AND MATCH (text) AGAINST (? IN BOOLEAN MODE)");
  $query->bind_param('sdds', $corpus, $ymin, $ymax, $q);
  $query->execute() or die('Query failed: ' . $mysqli->error);
  $query->bind_result($uri, $title, $author, $pub_year);

  $data = [];
  while ($query->fetch()) {
    $data[$uri] = ['title' => $title, 'author' => $author, 'pub_year' => $pub_year];
  }
  
  $results = [];
  foreach ($data as $uri => $metadata) {
    $result = ['uri' => $uri];
    $result['metadata'] = $metadata;
    $results[] = $result;
  }

  return $results;
}

function get_excerpts($corpus, $uri, $q, $excerpt_length, $max_excerpts)
{
  global $mysqli;

  $words = tokenize_query($q);

  $query = $mysqli->prepare("SELECT text
FROM text
WHERE corpus = ? AND uri = ?");
  $query->bind_param('ss', $corpus, $uri);
  $query->execute() or die('Query failed: ' . $mysqli->error);
  $query->bind_result($text);
  $query->fetch();
  
  $re = '/(.{0,' . ($excerpt_length - 1) . '}?)(^|[^\p{L}&\'’])(';
  $match_words = [];
  $first = true;
  foreach ($words as $word) {
    if ($first) {
      $first = false;
    } else {
      $re .= '|';
    }
    if ($word[0] == '"') {
      $word = mb_substr($word, 1, -1, 'UTF-8');
    }
    $match_words[] = mb_strtolower($word, 'UTF-8');
    $re .= preg_quote($word);
  }
  $re .= ')($|[^\p{L}&\'’])(?=(.{0,' . ($excerpt_length - 1) . '}))/Sius';

  preg_match_all($re, $text, $matches, PREG_SET_ORDER | PREG_OFFSET_CAPTURE);

  $nmatches_by_word = [];
  $excerpts = [];
  $last_right_excerpt = "";
  $last_excerpt_end_offset = -1;
  $nexcerpts = 0;
  $i = 0;
  foreach ($matches as $match) {
    $left_excerpt = htmlspecialchars($match[1][0] . $match[2][0], ENT_QUOTES) 
      . '<b>' . htmlspecialchars($match[3][0], ENT_QUOTES) . '</b>'
      . htmlspecialchars($match[4][0], ENT_QUOTES);
    $right_excerpt = htmlspecialchars($match[5][0], ENT_QUOTES);
    
    if ($match[0][1] <= $last_excerpt_end_offset) {
      // This excerpt overlaps with the last - merge them.
      $excerpts[$i - 1] .= $left_excerpt;
      $last_right_excerpt = $right_excerpt;

    } else {
      if ($i > 0) {
	$excerpts[$i - 1] .= $last_right_excerpt;
      }
      $excerpts[] = $left_excerpt;
      $last_right_excerpt = $right_excerpt;
      $i += 1;
    }

    $nexcerpts += 1;
    if ($nexcerpts == $max_excerpts) {
        break;
    }

    $last_excerpt_end_offset = $match[0][1] + mb_strlen($match[0][0] . $match[5][0], "UTF-8");

    $word_matched = mb_strtolower($match[3][0], 'UTF-8');
    foreach ($match_words as $word) {
      if ($word_matched == $word) {
	if (array_key_exists($word, $nmatches_by_word)) {
	  $nmatches_by_word[$word] += 1;
	} else {
	  $nmatches_by_word[$word] = 1;
	}
      }
    }
  }
  if ($last_right_excerpt != "") {
    $excerpts[$i - 1] .= $last_right_excerpt;
  }

  $nmatches = [];
  foreach ($match_words as $word) {
    if (array_key_exists($word, $nmatches_by_word)) {
      $nmatches[] = $nmatches_by_word[$word];
    } else {
      $nmatches[] = 0;
    }
  }

  return ['excerpts' => $excerpts, 'nmatches' => $nmatches];
}

function get_nmatches($corpus, $uri, $word)
{
  global $mysqli;

  $word = mb_strtolower($word, "UTF-8");

  $query = $mysqli->prepare("SELECT text
FROM text
WHERE corpus = ? AND uri = ?");
  $query->bind_param('ss', $corpus, $uri);
  $query->execute() or die('Query failed: ' . $mysqli->error);
  $query->bind_result($text);
  $query->fetch();
  
  $nmatches = preg_match_all('/(^|[^\p{L}&\'’])(' . preg_quote($word)
                             . ')($|[^\p{L}&\'’])/Siu', $text);
  
  return $nmatches;
}

function log_word_search($q)
{
    global $mysqli;
    $query = $mysqli->prepare("INSERT INTO word_search_log (query)
VALUES (?)");
    $query->bind_param('s', $q);
    $query->execute() or die('Query failed: ' . $mysqli->error);
}

function highlight_search_terms($content, $q)
{
    $toks = tokenize_query($q);
    foreach ($toks as $tok) {
      if ($tok[0] == '"') {
	// Quoted string - this case is a bit complex because the individual words
	// that match parts of the string might have span tags around them.  Also,
	// I know that REs can't be used to parse general HTML.  The only tags that
	// can appear in $content are the ones produced by gen_annotated_text, which
	// can't include recursion.
	$subtoks = preg_split('/([^\w]+)/', mb_substr($tok, 1, -1, 'UTF-8'), -1,
			      PREG_SPLIT_DELIM_CAPTURE | PREG_SPLIT_NO_EMPTY);
	$re = '/(^|[^\p{L}&\'’])(';
	foreach ($subtoks as $subtok) {
	  if (preg_match('/^\w/', $subtok)) {
	    $re .= '(?:<[^>]+>)?' . preg_quote($subtok) . '(?:<[^>]+>)?';
	  } else {
	    $re .= preg_quote($subtok);
	  }
	}
	$re .= ')($|[^\p{L}&\'’])/Siu';
        $content = preg_replace($re, '\1<span data-q="q">\2</span>\3', $content);
      } else {
        $content = preg_replace('/(^|[^\p{L}&\'’])(' . preg_quote($tok)
                                    . ')($|[^\p{L}&\'’])/Siu',
                                '\1<span data-q="q">\2</span>\3', $content);
      }
    }
    return $content;
}

?>
