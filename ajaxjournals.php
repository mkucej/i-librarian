<?php
include_once 'data.php';
include_once 'functions.php';
database_connect($database_path, 'library');
$query = $dbHandle->quote ('%'.$_GET['term'].'%');
if ($_GET['search'] == 'journal') $result = $dbHandle->query("SELECT DISTINCT journal FROM library WHERE journal LIKE $query ORDER BY journal ASC");
if ($_GET['search'] == 'secondary_title') $result = $dbHandle->query("SELECT DISTINCT secondary_title FROM library WHERE secondary_title LIKE $query ORDER BY secondary_title ASC");
$dbHandle = null;
$journals = $result->fetchAll(PDO::FETCH_COLUMN);
echo json_encode($journals);
?>