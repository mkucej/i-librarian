<?php
include_once 'data.php';
include_once 'functions.php';
session_write_close();

// ONLY ADMIN CAN DO THIS
if (!isset($_SESSION['auth']) || $_SESSION['permissions'] !== 'A') die();

$allowed_databases = array ('library', 'fulltext', 'users', 'discussions', 'history');

if (!empty($_GET['db']) && in_array($_GET['db'], $allowed_databases)) {
    if ($_GET['db'] == 'users') {
        database_connect($usersdatabase_path, 'users');
    } else {
        database_connect($database_path, $_GET['db']);
    }
    $dbHandle->exec('VACUUM');
    $dbHandle = null;
    if ($_GET['db'] == 'users') {
        $dbsize = filesize($usersdatabase_path.DIRECTORY_SEPARATOR.'users.sq3');
    } else {
        $dbsize = filesize($database_path.DIRECTORY_SEPARATOR.$_GET['db'].'.sq3');
    }
    if ($dbsize < 1048576) $size = round($dbsize / 1024, 1).' kB';
    if ($dbsize >= 1048576) $size = round($dbsize / 1048576, 1).' MB';
    if ($dbsize >= 1073741824) $size = round($dbsize / 1073741824, 1).' GB';
    print $size;
}
?>