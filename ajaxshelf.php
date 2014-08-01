<?php
include_once 'data.php';
include_once 'functions.php';

if (isset($_GET['file']) && isset($_SESSION['auth'])) {

    database_connect($database_path, 'library');

    $user_query = $dbHandle->quote($_SESSION['user_id']);
    $file_query = $dbHandle->quote($_GET['file']);

    $result = $dbHandle->query("SELECT rowid FROM shelves WHERE userID=$user_query AND fileID=$file_query LIMIT 1");
    $relation = $result->fetchColumn();
    $result = null;

    if (!$relation) {
        $dbHandle->beginTransaction();
        $result = $dbHandle->query("SELECT COUNT(*) FROM library WHERE id=$file_query");
        $exists = $result->fetchColumn();
        $result = null;
        if ($exists == 1) {
            $update = $dbHandle->exec("INSERT OR IGNORE INTO shelves (userID,fileID) VALUES ($user_query,$file_query)");
            $dbHandle->commit();
            if ($update) echo 'added';
        } else {
            $dbHandle->rollBack();
            echo 'Error! This item does not exist anymore.';
        }
    } else {
        $update = $dbHandle->exec("DELETE FROM shelves WHERE rowid=$relation");
        if (isset($_GET['selection']) && $_GET['selection'] == 'shelf') {

            $export_files = read_export_files(0);
            unset($export_files[array_search($_GET['file'], $export_files)]);
            $export_files = array_values($export_files);
            save_export_files($export_files);
        }
        if ($update)
            echo 'removed';
    }
    $dbHandle = null;
    
    // DELETE SHELF CACHE
    @unlink($temp_dir . DIRECTORY_SEPARATOR . 'lib_' . session_id() . DIRECTORY_SEPARATOR . 'shelf_files');
}
?>