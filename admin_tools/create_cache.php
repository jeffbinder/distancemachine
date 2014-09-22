<?php

// Creates a cache of the usage periods and dicts for the n most commonly used words.

set_include_path('../public_html/');
include 'application.php';

// 500 most common words in Project Gutenberg
$words = "the of and to in i that was his he it with is for as had you not be her on at
by which have or from this him but all she they were my are me one their so an
said them we who would been will no when there if more out up into do any your
what has man could other than our some very time upon about may its only now
like little then can should made did us such a great before must two these see
know over much down after first Mr good men own never most old shall day where
those came come himself way work life without go make well through being long
say might how am too even def again many back here think every people went
same last thought away under take found hand eyes still place while just also
young yet though against things get ever give god years off face nothing right
once another left part saw house world head three took new love always mrs put
night each king between tell mind heart few because thing whom far seemed
looked called whole de set both got find done heard look name days told let
lord country asked going seen better p having home knew side something moment
father among course hands woman enough words mother soon full end gave room
almost small thou cannot water want however light quite brought nor word whose
given door best turned taken does use morning myself Gutenberg felt until
since power themselves used rather began present voice others white works less
money next poor death stood form within together till thy large matter kind
often certain herself year friend half order round true anything keep sent
wife means believe passed feet near public state son hundred children thus
hope alone above case dear thee says person high read city already received
fact gone girl known hear times least perhaps sure indeed english open body
itself along land return leave air nature answered either law help lay point
child letter four wish fire cried 2 women speak number therefore hour friends
held free war during several business whether er manner second reason replied
united call general why behind became john become dead earth boy lost forth
thousand looking I'll family soul feel coming England spirit question care
truth ground really rest mean different making possible fell towards human
kept short town following need cause met evening returned five strong able
french live lady subject sn answer sea fear understand hard terms doubt around
ask arms turn sense seems black bring followed beautiful close dark hold
character sort sight ten show party fine ye ready story common book electronic
talk account mark interest written can't bed necessary age else force idea
longer art spoke across brother early ought sometimes line saying table
appeared river continued eye ety sun information later everything reached
suddenly past hours strange deep change miles feeling act meet paid";
$words = str_replace("\n", " ", $words);
$words = explode(" ", $words);

$mysqli = mysqli_connect($mysql_server, $mysql_username, $mysql_passwd)
  or die('Could not connect: ' . $mysqli->connection_error);
mysqli_select_db($mysqli, $main_db_name) or die('Could not select database');

$ngrams_data = array();
$dict_data = array();
foreach ($corpora as $corpus) {
  $ngrams_data[$corpus] = array();
}

$query = $mysqli->prepare("SELECT classes FROM word_classes WHERE word = ? AND corpus = ?");
$query->bind_param('ss', $word, $corpus);
$query->bind_result($classes);
foreach ($words as $word) {
  foreach ($corpora as $corpus) {
    $query->execute() or die('Query failed: ' . $mysqli->error);
    if (!$query->fetch()) {
        $classes = "";
    }
    $ngrams_data[$corpus][$word] = $classes;
  }
}

$cache_data = array();
foreach ($words as $word) {
  $in_dicts = get_dicts_for_word($word);
  $dict_data[$word] = $in_dicts;
}

mysqli_close($mysqli);

$data = ["ngrams" => $ngrams_data, "dict" => $dict_data];
$data = json_encode($data);
file_put_contents("CACHE", $data)

?>