<?php

date_default_timezone_set('America/New_York'); 

$start_year = ['us' => 1800, 'gb' => 1800, 'eebotcp1' => 1500];
$end_year = ['us' => 2009, 'gb' => 2009, 'eebotcp1' => 1700];
$data_start_year = ['us' => 1750, 'gb' => 1750, 'eebotcp1' => 1500];
$data_end_year = ['us' => 2009, 'gb' => 2009, 'eebotcp1' => 1700];

$max_freq = 1000000;
$max_linelen = 80;
$savedir = '/var/distancemachine/savedtexts/';
$tmpdir = '/var/distancemachine/unsavedtexts/';
$lockfile = '/var/distancemachine/LOCK';
$cachefile = '/var/distancemachine/CACHE';
$mysql_server = 'localhost';
$mysql_username = 'words';
$mysql_passwd = '';
$main_db_name = 'wordusage';
$wordnet_db_name = 'wordnet30';
$dict_db_name = 'dict';
$log_ip_addresses = false;
$log_word_lookups = true;

$archive_mode = false;
$archive_dir = '/var/distancemachine/archive/';
$excerpt_length = 40;
$num_excerpts = 15;

$corpora = ['us', 'gb', 'eebotcp1'];

$dicts = [];
//$dicts = ["dict1", "dict2"];

?>