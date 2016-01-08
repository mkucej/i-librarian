<?php

include_once 'data.php';
include_once 'functions.php';

session_write_close();

if (!empty($_GET['file'])) {

    $pdf_path = IL_PDF_PATH . DIRECTORY_SEPARATOR . get_subfolder($_GET['file']);

    database_connect(IL_DATABASE_PATH, 'library');
    $file_query = $dbHandle->quote(intval($_GET['file']));
    $result = $dbHandle->query("SELECT file FROM library WHERE id=$file_query LIMIT 1");
    $filename = $result->fetchColumn();
    $dbHandle = null;

    ##########	extract text from pdf	##########

    if (is_file($pdf_path . DIRECTORY_SEPARATOR . $filename)) {

        system(select_pdftotext() . ' -enc UTF-8 "' . $pdf_path . DIRECTORY_SEPARATOR . $filename . '" "' . IL_TEMP_PATH . DIRECTORY_SEPARATOR . $filename . '.txt"', $ret);

        if (is_file(IL_TEMP_PATH . DIRECTORY_SEPARATOR . $filename . ".txt")) {

            $string = trim(file_get_contents(IL_TEMP_PATH . DIRECTORY_SEPARATOR . $filename . ".txt"));
            unlink(IL_TEMP_PATH . DIRECTORY_SEPARATOR . $filename . ".txt");

            $string = preg_replace ('/[^\x{0009}\x{000a}\x{000d}\x{0020}-\x{D7FF}\x{E000}-\x{FFFD}]+/u', ' ', $string);
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
                $answer = "There is no text to extract.";
            }
        } else {
            $answer = "Text extracting not allowed.";
        }
    } else {
        $answer = "File not found.";
    }
}
if (isset($answer))
    print $answer;
?>