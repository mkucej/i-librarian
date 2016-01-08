<?php

include_once 'data.php';
include_once 'functions.php';

session_write_close();

if ($_GET['binary'] == 'pdftotext') {

    exec(select_pdftotext() . ' -enc UTF-8 test.pdf "' . IL_TEMP_PATH . DIRECTORY_SEPARATOR . 'test.txt"');

    if (file_exists(IL_TEMP_PATH . DIRECTORY_SEPARATOR . 'test.txt')) {
        unlink(IL_TEMP_PATH . DIRECTORY_SEPARATOR . 'test.txt');
        die('OK');
    } else {
        die();
    }
} elseif ($_GET['binary'] == 'pdfinfo') {

    exec(select_pdfinfo() . ' test.pdf', $output);

    if (!empty($output)) {
        die('OK');
    } else {
        die();
    }
} elseif ($_GET['binary'] == 'pdftohtml') {

    exec(select_pdftohtml() . ' -q -noframes -enc UTF-8 -nomerge -c -xml test.pdf "' . IL_TEMP_PATH . DIRECTORY_SEPARATOR . 'test"');

    if (file_exists(IL_TEMP_PATH . DIRECTORY_SEPARATOR . 'test.xml')) {
        unlink(IL_TEMP_PATH . DIRECTORY_SEPARATOR . 'test.xml');
        die('OK');
    } else {
        die();
    }
} elseif ($_GET['binary'] == 'pdfdetach') {

    exec(select_pdfdetach() . ' -saveall -o "' . IL_TEMP_PATH . '" test.pdf');

    if (is_readable(IL_TEMP_PATH . DIRECTORY_SEPARATOR . 'test.odt') && filesize(IL_TEMP_PATH . DIRECTORY_SEPARATOR . 'test.odt') > 0) {
        unlink(IL_TEMP_PATH . DIRECTORY_SEPARATOR . 'test.odt');
        die('OK');
    } else {
        die();
    }
} elseif ($_GET['binary'] == 'ghostscript') {

    exec(select_ghostscript() . ' -sDEVICE=png16m -r15 -dTextAlphaBits=1 -dGraphicsAlphaBits=1 -dFirstPage=1 -dLastPage=1 -o "' . IL_TEMP_PATH . DIRECTORY_SEPARATOR . 'test.png" test.pdf');

    if (file_exists(IL_TEMP_PATH . DIRECTORY_SEPARATOR . 'test.png')) {
        unlink(IL_TEMP_PATH . DIRECTORY_SEPARATOR . 'test.png');
        die('OK');
    } else {
        die();
    }
} elseif ($_GET['binary'] == 'tesseract') {

    exec(select_tesseract() . ' "' . __DIR__ . DIRECTORY_SEPARATOR . 'test.bmp" "' . IL_TEMP_PATH . DIRECTORY_SEPARATOR . 'test-tesseract"');

    if (is_readable(IL_TEMP_PATH . DIRECTORY_SEPARATOR . 'test-tesseract.txt') && filesize(IL_TEMP_PATH . DIRECTORY_SEPARATOR . 'test-tesseract.txt') > 0) {
        unlink(IL_TEMP_PATH . DIRECTORY_SEPARATOR . 'test-tesseract.txt');
        die('OK');
    } else {
        die();
    }
} elseif ($_GET['binary'] == 'soffice') {

    if (PHP_OS == 'Linux' || PHP_OS == 'Darwin')
        putenv('HOME=' . IL_TEMP_PATH);
    exec(select_soffice() . ' --headless --convert-to pdf --outdir "' . IL_TEMP_PATH . '" "' . __DIR__ . DIRECTORY_SEPARATOR . 'test.odt"');
    if (PHP_OS == 'Linux' || PHP_OS == 'Darwin')
        putenv('HOME=""');
    $converted_file = IL_TEMP_PATH . DIRECTORY_SEPARATOR . 'test.pdf';
    if (!is_file($converted_file)) {
        die();
    } else {
        unlink($converted_file);
        die('OK');
    }
}

?>
