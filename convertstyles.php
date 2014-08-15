<?php

include_once 'data.php';
include_once 'functions.php';
session_write_close();

// create and populate styles.sq3
try {
    $dbHandle = new PDO('sqlite:/var/www/html/i-librarian/js/csl/styles/styles.sq3');
} catch (PDOException $e) {
    print "Error: " . $e->getMessage() . "<br/>";
    print "PHP extensions PDO and PDO_SQLite must be installed.";
    die();
}

//$dbHandle->exec('PRAGMA journal_mode = DELETE');

//$dbHandle->exec("CREATE TABLE IF NOT EXISTS styles"
//        . " (style_id INTEGER PRIMARY KEY,"
//        . " title TEXT NOT NULL DEFAULT '',"
//        . " style BLOB NOT NULL DEFAULT '')");
//
//$styles = glob('/var/www/html/i-librarian/js/csl/styles/*.csl');
//
//$stmt = $dbHandle->prepare("INSERT INTO styles (title,style) VALUES (:title,:style)");
//
//$title = '';
//$filename = '';
//$blob = '';
//
//$dbHandle->beginTransaction();
//
//foreach ($styles as $style) {
//
//    $stmt->bindValue(':title', $title, PDO::PARAM_STR);
//    $stmt->bindValue(':style', $blob, PDO::PARAM_LOB);
//
//    $xml = simplexml_load_file($style);
//    $title = strtolower((string) $xml->info->title);
//    $blob = gzcompress(file_get_contents($style), 9);
//
//    $stmt->execute();
//}
//
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
