<?php
include_once 'data.php';
include_once 'functions.php';


try {
    $dbHandle = new PDO('sqlite:' . __DIR__ . DIRECTORY_SEPARATOR . 'styles.sq3');
} catch (PDOException $e) {
    print "Error: " . $e->getMessage() . "<br/>";
    print "PHP extensions PDO and PDO_SQLite must be installed.";
    die();
}

$title_q = $dbHandle->quote(strtolower('%'. $_GET['term'].'%'));
$result = $dbHandle->query('SELECT title FROM styles WHERE title LIKE ' . $title_q);
$dbHandle = null;

$titles = $result->fetchAll(PDO::FETCH_COLUMN);
foreach ($titles as $title) {
    $output[] = ucwords($title);
}
echo json_encode($output);
