<?php

include_once 'data.php';
session_cache_limiter('none');
session_write_close();

//PATH SUPPLEMENT OR PNGS
if (isset($_GET['attachment'])) {
    $path = $library_path . 'supplement';
    $file = str_replace("/", "", $_GET['attachment']);
    $file = str_replace("\\", "", $file);
}
if (isset($_GET['png'])) {
    $path = $library_path . 'pngs';
    $file = str_replace("/", "", $_GET['png']);
    $file = str_replace("\\", "", $file);
}

//MODE INLINE OR ATTACHMENT
$mode = 'attachment';
if (isset($_GET['mode']) && $_GET['mode'] == 'inline')
    $mode = 'inline';


if (!empty($file) && is_file($path . DIRECTORY_SEPARATOR . $file)) {

    if (isset($_GET['attachment'])) $filename = substr(urldecode($file), 5);
    if (isset($_GET['png'])) $filename = urldecode($file);

    $type = 'application/octet-stream';
    if (extension_loaded('fileinfo')) {
        $finfo = new finfo(FILEINFO_MIME);
        $type = $finfo->file($path . DIRECTORY_SEPARATOR . $file);
    }

    //CACHE THIS FILE
    $seconds_to_cache = 604800;
    $ts = gmdate("D, d M Y H:i:s", time() + $seconds_to_cache) . " GMT";
    header("Expires: $ts");
    header("Pragma: private");
    header("Cache-Control: max-age=$seconds_to_cache");
    header("Cache-Control: private");
    header('Last-Modified: '.gmdate(DATE_RFC1123,filemtime($path . DIRECTORY_SEPARATOR . $file)));

    header("Content-Type: " . $type);
    header("Content-Disposition: " . $mode . "; filename=\"$filename\"");
    header('Content-Length: ' . filesize($path . DIRECTORY_SEPARATOR . $file));
    ob_clean();
    flush();
    readfile($path . DIRECTORY_SEPARATOR . $file);
}
?>