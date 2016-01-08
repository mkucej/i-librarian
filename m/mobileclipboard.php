<?php

include_once '../data.php';
include_once '../functions.php';

database_connect(IL_DATABASE_PATH, 'library');
attach_clipboard($dbHandle);
$quoted_path = $dbHandle->quote(IL_DATABASE_PATH . DIRECTORY_SEPARATOR . 'history.sq3');
$dbHandle->exec("ATTACH DATABASE $quoted_path as history");

$dbHandle->beginTransaction();

if (isset($_GET['action']) && $_GET['action'] == 'add') {
    
    $dbHandle->exec("INSERT OR IGNORE INTO clipboard.files (id) SELECT itemID FROM history.`" . $_SESSION['display_files'] . "` ORDER BY id DESC");
} else {
    
    $dbHandle->exec("DELETE FROM clipboard.files WHERE id IN (SELECT itemID FROM history.`" . $_SESSION['display_files'] . "` ORDER BY id)");

}
    
$dbHandle->commit();
$dbHandle = null;
