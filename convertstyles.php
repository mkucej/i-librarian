<?php
// this is a utility script to compress styles to an SQLite database

include_once 'data.php';
include_once 'functions.php';
session_write_close();

// create and populate styles.sq3
//$dbHandle = database_connect(__DIR__, 'styles');
//$dbHandle->exec('PRAGMA journal_mode = DELETE');
//$dbHandle->exec("CREATE TABLE IF NOT EXISTS styles"
//        . " (style_id INTEGER PRIMARY KEY,"
//        . " title TEXT NOT NULL DEFAULT '',"
//        . " style BLOB NOT NULL DEFAULT '')");
//$styles = glob('C:/Users/martin/Documents/i-librarian/styles-master/*.csl');
//$stmt = $dbHandle->prepare("INSERT INTO styles (title,style) VALUES (:title,:style)");
//$title = '';
//$filename = '';
//$blob = '';
//$dbHandle->beginTransaction();
//foreach ($styles as $style) {
//    $stmt->bindValue(':title', $title, PDO::PARAM_STR);
//    $stmt->bindValue(':style', $blob, PDO::PARAM_LOB);
//    $xml = simplexml_load_file($style);
//    $title = strtolower((string) $xml->info->title);
//    $blob = gzcompress(file_get_contents($style), 9);
//    $stmt->execute();
//}
//$dbHandle->commit();

// select a style
//$title_q = $dbHandle->quote(strtolower($_GET['title']));
//$result = $dbHandle->query('SELECT style FROM styles WHERE title=' . $title_q);
//print_r($dbHandle->errorInfo());
//$style = $result->fetchColumn();
//$a = gzuncompress($style);
//
//var_dump(htmlspecialchars($a));

$dbHandle = null;
