<?php

date_default_timezone_set('America/New_York'); 

$start_year = 1800;
$end_year = (int)date("Y");
$data_start_year = 1750;
$data_end_year = 2009;
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

$corpora = ['us', 'gb'];

$dicts = [];
//$dicts = ["dict1", "dict2"];

?>