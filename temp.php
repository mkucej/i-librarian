<?php

include_once 'data.php';

$pdf = '';
if (preg_match('/^lib_\S{15}\.pdf$/i', $_GET['tempfile']) > 0 && file_exists($temp_dir . DIRECTORY_SEPARATOR . $_GET['tempfile'])) {

    header('Content-type: application/pdf');
    header('Content-Disposition: inline; filename="' . $_GET['tempfile'] . '"');
    ob_clean();
    flush();
    readfile($temp_dir . DIRECTORY_SEPARATOR . $_GET['tempfile']);
} else {

    print "File $_GET[tempfile] does not exist.";
}
?>