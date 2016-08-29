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

        $answer = recordFulltext($_GET['file'], $filename);

    } else {

        $answer = "File not found.";
    }
}
if (!empty($answer)) {
    print $answer;
}
