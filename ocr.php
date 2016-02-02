<?php

include_once 'data.php';
include_once 'functions.php';
session_write_close();

database_connect(IL_DATABASE_PATH, 'library');
$file_query = $dbHandle->quote(intval($_GET['file']));
$result = $dbHandle->query("SELECT file FROM library WHERE id=$file_query LIMIT 1");
$file = $result->fetchColumn();
$dbHandle = null;

if (is_file(IL_PDF_PATH . DIRECTORY_SEPARATOR . get_subfolder($file) . DIRECTORY_SEPARATOR . $file)) {

    exec(select_ghostscript() . ' -dSAFER -dBATCH -dNOPAUSE -sDEVICE=bmp16m -r300 -dTextAlphaBits=4 -dGraphicsAlphaBits=4 -dDOINTERPOLATE -o "' . IL_TEMP_PATH . DIRECTORY_SEPARATOR . $file . '.%03d.bmp" "' . IL_PDF_PATH . DIRECTORY_SEPARATOR . get_subfolder($file) . DIRECTORY_SEPARATOR . $file . '"');

    $file_arr = glob(IL_TEMP_PATH . DIRECTORY_SEPARATOR . '*.bmp');

    if (is_array($file_arr)) {

        set_time_limit(600);

        for ($i = 0; $i < count($file_arr); $i++) {
            exec(select_tesseract() . ' "' . $file_arr[$i] . '" "' . IL_TEMP_PATH . DIRECTORY_SEPARATOR . $file . '.' . $i . '"');
            if (is_file(IL_TEMP_PATH . DIRECTORY_SEPARATOR . $file . '.' . $i . '.txt')) {
                file_put_contents(IL_TEMP_PATH . DIRECTORY_SEPARATOR . $file . 'final.txt', file_get_contents(IL_TEMP_PATH . DIRECTORY_SEPARATOR . $file . '.' . $i . '.txt'), FILE_APPEND);
                unlink(IL_TEMP_PATH . DIRECTORY_SEPARATOR . $file . '.' . $i . '.txt');
                unlink($file_arr[$i]);
            } else {
                die('OCR software not functional.');
            }
        }

        $string = file_get_contents(IL_TEMP_PATH . DIRECTORY_SEPARATOR . $file . 'final.txt');
        unlink(IL_TEMP_PATH . DIRECTORY_SEPARATOR . $file . 'final.txt');

        $string = preg_replace('/[^\x{0009}\x{000a}\x{000d}\x{0020}-\x{D7FF}\x{E000}-\x{FFFD}]+/u', ' ', $string);
        $string = trim($string);

        if (!empty($string)) {

            $order = array("\r\n", "\n", "\r");
            $string = str_replace($order, ' ', $string);
            $string = preg_replace('/\s{2,}/ui', ' ', $string);

            $output = null;

            database_connect(IL_DATABASE_PATH, 'fulltext');
            $file_query = $dbHandle->quote(intval($_GET['file']));
            $fulltext_query = $dbHandle->quote($string);
            $dbHandle->beginTransaction();
            $dbHandle->exec("DELETE FROM full_text WHERE fileID=$file_query");
                $output = $dbHandle->exec("INSERT INTO full_text (fileID,full_text) VALUES ($file_query,$fulltext_query)");
            $dbHandle->commit();
            $dbHandle = null;

            if (!$output)
                $answer = 'Database error.';
        } else {
            $answer = "OCR text extraction failed.";
        }
    } else {
        $answer = "Ghostscipt not functional.";
    }
} else {
    $answer = "PDF file not found.";
}

if (isset($answer))
    echo $answer;
?>