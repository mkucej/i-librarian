<?php

include_once '../data.php';
include_once '../functions.php';
session_write_close();

$path = IL_SUPPLEMENT_PATH . DIRECTORY_SEPARATOR . get_subfolder($_GET['attachment']) . DIRECTORY_SEPARATOR;

if (!empty($_GET['attachment']) && is_file($path . $_GET['attachment']) && strstr($_GET['attachment'], "\\") === FALSE && strstr($_GET['attachment'], "/") === FALSE) {

    $filename = substr(urldecode($_GET['attachment']), 5);

    $type = 'application/octet-stream';
    if (extension_loaded('fileinfo')) {
        $finfo = new finfo(FILEINFO_MIME);
        $type = $finfo->file($path . $_GET['attachment']);
    }

    header("Content-Type: " . $type);
    header("Content-Disposition: attachment; filename=\"$filename\"");
    header("Pragma: no-cache");
    header("Expires: 0");
    header('Content-Length: ' . filesize($path . $_GET['attachment']));
    ob_clean();
    flush();
    readfile($path . $_GET['attachment']);
}
?>