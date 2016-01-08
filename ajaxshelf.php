<?php
include_once 'data.php';
include_once 'functions.php';

if (isset($_GET['file']) && isset($_SESSION['auth'])) {

    database_connect(IL_DATABASE_PATH, 'library');

    $user_query = $dbHandle->quote($_SESSION['user_id']);
    $file_query = $dbHandle->quote($_GET['file']);

    $dbHandle->beginTransaction();

    $result = $dbHandle->query("SELECT rowid FROM shelves WHERE userID=$user_query AND fileID=$file_query LIMIT 1");
    $relation = $result->fetchColumn();
    $result = null;

    if (!$relation) {

        $result = $dbHandle->query("SELECT COUNT(*) FROM library WHERE id=$file_query");
        $exists = $result->fetchColumn();
        $result = null;
        if ($exists == 1) {
            $update = $dbHandle->exec("INSERT OR IGNORE INTO shelves (userID,fileID) VALUES ($user_query,$file_query)");

            if ($update) echo 'added';
        } else {
            $dbHandle->rollBack();
            echo 'Error! This item does not exist anymore.';
        }
    } else {
        $update = $dbHandle->exec("DELETE FROM shelves WHERE rowid=$relation");
        if ($update)
            echo 'removed';
    }
    
    $dbHandle->commit();
    $dbHandle = null;
}
