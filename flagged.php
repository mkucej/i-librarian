<?php

include_once 'data.php';
include_once 'functions.php';

if (!empty($_GET['database'])) {
    $database = $_GET['database'];
    $allowed_databases = array('pubmed', 'pmc', 'nasaads', 'arxiv');
    if (!in_array($database, $allowed_databases))
        die();
} else {
    die();
}

if (!empty($_SESSION['user_id'])) {
    $user_id = intval($_SESSION['user_id']);
} else {
    die();
}

// CLEAN DOWNLOAD CACHE
$clean_files = glob(IL_TEMP_PATH . DIRECTORY_SEPARATOR . 'lib_' . session_id() . DIRECTORY_SEPARATOR . 'page_*_download', GLOB_NOSORT);
if (is_array($clean_files)) {
    foreach ($clean_files as $clean_file) {
        if (is_file($clean_file) && is_writable($clean_file))
            @unlink($clean_file);
    }
}

database_connect(IL_DATABASE_PATH, 'library');

if (!empty($_GET['empty'])) {
    $dbHandle->exec("DELETE FROM flagged WHERE userID=" . $user_id . " AND database='" . $database . "'");
    die();
}

if (!empty($_GET['uid'])) {

    $uid_query = $dbHandle->quote($_GET['uid']);

    $result = $dbHandle->query("SELECT id FROM flagged WHERE userID=" . $user_id . " AND database='" . $database . "' AND uid=" . $uid_query . " LIMIT 1");
    $relation = $result->fetchColumn();
    $result = null;

    if (!$relation) {
        //HOW MANY FLAGGED?
        $result = $dbHandle->query("SELECT count(*) FROM flagged WHERE userID=" . $user_id . " AND database='" . $database . "'");
        $flagged_count = $result->fetchColumn();
        $result = null;
        //FLAG IF < 100
        if ($flagged_count > 99)
            die();
        $update = $dbHandle->exec("INSERT OR IGNORE INTO flagged (userID,database,uid) VALUES ($user_id,'" . $database . "',$uid_query)");
        if ($update)
            echo 'added';
    } else {
        //UNFLAG
        $update = $dbHandle->exec("DELETE FROM flagged WHERE id=$relation");
        if ($update)
            echo 'removed';
    }
} else {

    //RETURN FLAGGED AS JSON
    $result = $dbHandle->query("SELECT uid FROM flagged WHERE userID=" . $user_id . " AND database='" . $database . "'");
    $uid_list = $result->fetchAll(PDO::FETCH_COLUMN);
    $result = null;

    print json_encode($uid_list);
}

$dbHandle = null;
?>