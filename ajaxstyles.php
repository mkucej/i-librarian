<?php
include_once 'data.php';
include_once 'functions.php';

$output = array();

$dbHandle = database_connect(__DIR__, 'styles');

$title_q = $dbHandle->quote(strtolower('%'. $_GET['term'].'%'));
$result = $dbHandle->query('SELECT title FROM styles WHERE title LIKE ' . $title_q);
$dbHandle = null;

$titles = $result->fetchAll(PDO::FETCH_COLUMN);
foreach ($titles as $title) {
    $output[] = ucwords($title);
}
echo json_encode($output);
