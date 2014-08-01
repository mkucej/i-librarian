<?php
include_once 'data.php';
include_once '../functions.php';

$export_files = array();
$export_files = read_export_files(0);

if (isset($_GET['action']) && $_GET['action'] == 'add') {
    
    $_SESSION['session_clipboard'] = array();
    $_SESSION['session_clipboard'] = $export_files;
    
} else {
    
    $_SESSION['session_clipboard'] = array();
    
    if (isset($_GET['selection']) && $_GET['selection'] == 'clipboard') {
            save_export_files(array());
    }
}
?>