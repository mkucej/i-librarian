<?php

include_once 'data.php';
include_once '../functions.php';

if (isset($_GET['file'])) {
    if (!isset($_SESSION['session_clipboard'])) {
        database_connect($database_path, 'library');
        $file_query = $dbHandle->quote($_GET['file']);
        $result = $dbHandle->query("SELECT COUNT(*) FROM library WHERE id=$file_query");
        $exists = $result->fetchColumn();
        $result = null;
        $dbHandle = null;
        if ($exists == 1) {
            $_SESSION['session_clipboard'][] = $_GET['file'];
            echo "added";
        } else {
            echo 'Error! This item does not exist anymore.';
        }
    } else {
        if (!in_array($_GET['file'], $_SESSION['session_clipboard'])) {
            $_SESSION['session_clipboard'][] = $_GET['file'];
            $_SESSION['session_clipboard'] = array_unique($_SESSION['session_clipboard']);
            echo "added";
        } else {

            $key = array_search($_GET['file'], $_SESSION['session_clipboard']);
            unset($_SESSION['session_clipboard'][$key]);

            if (isset($_GET['selection']) && $_GET['selection'] == 'clipboard') {

                $export_files = read_export_files(0);

                $id = array_search($_GET['file'], $export_files);

                if ($id !== false) {
                    unset($export_files[$id]);
                    $export_files = array_values($export_files);
                    save_export_files($export_files);
                }
            }
            echo "removed";
        }
    }
}
?>