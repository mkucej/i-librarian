<?php

include_once 'data.php';
include_once 'functions.php';

if (isset($_GET['file'])) {

    database_connect(IL_DATABASE_PATH, 'library');

    attach_clipboard($dbHandle);
    
    $file_query = $dbHandle->quote($_GET['file']);

    $dbHandle->beginTransaction();

    // Does item exist in clipboard?
    $result = $dbHandle->query("SELECT COUNT(*) FROM clipboard.files WHERE id=$file_query");
    $exists = $result->fetchColumn();
    $result = null;

    if ($exists !== '1') {

        // Does item still exist in library?
        $result = $dbHandle->query("SELECT COUNT(*) FROM library WHERE id=$file_query");
        $exists = $result->fetchColumn();
        $result = null;

        if ($exists !== '1')
            die('Error! This item does not exist anymore.');

        // Can't add over 100,000
        $result = $dbHandle->query("SELECT count(*) FROM clipboard.files");
        $count = $result->fetchColumn();
        $result = null;
        if ($count >= 100000) {
            $dbHandle->rollBack();
            echo 'Error! Clipboard can hold up to 100,000 items.';
            die();
        }

        // Add item to clipboard.
        $dbHandle->exec("INSERT OR IGNORE INTO clipboard.files (id) VALUES($file_query)");
        echo "added";
    } else {

        // Remove from clipboard.
        $dbHandle->exec("DELETE FROM clipboard.files WHERE id=$file_query");
        echo "removed";
    }

    $dbHandle->commit();

    $dbHandle->exec("DETACH DATABASE clipboard");
    $dbHandle = null;
}    