<?php
include_once 'data.php';
include_once 'functions.php';

session_write_close();

// Introduction.

if (!empty($_GET['intro'])) {
    ?>

    <div class="item-sticker ui-widget-content ui-corner-all" style="margin:auto;margin-top:100px;width:340px">
        <div class="ui-widget-header ui-dialog-titlebar ui-corner-top titles file-title" style="border:0;text-align:center">
            Batch PDF re-indexing
        </div>
        <div class="separator" style="margin:0"></div>
        <div class="alternating_row ui-corner-bottom" style="padding:4px 12px;overflow:auto;">
            <p style="text-align:justify">
                This tool will re-extract text from all existing PDF files. Beware, the process
                can take several hours, if the number of files is large.
            </p>
        </div>
        <div class="separator" style="margin:0"></div>
        <div class="alternating_row ui-corner-bottom" style="padding:4px 7px;overflow:auto;vertical-align: middle">
            <button>Start</button>
        </div>
    </div>

    <?php
    die();
}

// Script.
$order = array("\r\n", "\n", "\r");

echo '<div style="padding:1em 2em">';
echo '<h3>Batch PDF re-indexing.</h3>';
echo '<h4>Error log:</h4>';

// Iterate all PDF files.
$glob = new GlobIterator(IL_PDF_PATH . DIRECTORY_SEPARATOR . '[0-9][0-9]' . DIRECTORY_SEPARATOR . '*.pdf');

foreach ($glob as $pdf) {
    
    $answer = array();

    $file_path = $pdf->getPathname();
    $file_name = $pdf->getFilename();
    $file_id = intval(basename($pdf->getFilename(), '.pdf'));

    // Extract text from PDF.

    if (is_readable($file_path)) {

        system(select_pdftotext() . ' -enc UTF-8 "' . $file_path . '" "' . IL_TEMP_PATH . DIRECTORY_SEPARATOR . $file_name . '.txt"', $ret);

        if (is_file(IL_TEMP_PATH . DIRECTORY_SEPARATOR . $file_name . ".txt")) {

            $string = trim(file_get_contents(IL_TEMP_PATH . DIRECTORY_SEPARATOR . $file_name . ".txt"));
            unlink(IL_TEMP_PATH . DIRECTORY_SEPARATOR . $file_name . ".txt");

            $string = preg_replace('/[^\x{0009}\x{000a}\x{000d}\x{0020}-\x{D7FF}\x{E000}-\x{FFFD}]+/u', ' ', $string);
            $string = trim($string);

            if (!empty($string)) {

                $string = str_replace($order, ' ', $string);
                $string = preg_replace('/\s{2,}/ui', ' ', $string);

                $output = false;

                database_connect(IL_DATABASE_PATH, 'fulltext');
                $file_query = $dbHandle->quote($file_id);
                $fulltext_query = $dbHandle->quote($string);
                $dbHandle->beginTransaction();
                $dbHandle->exec("DELETE FROM full_text WHERE fileID=$file_query");
                $output = $dbHandle->exec("INSERT INTO full_text (fileID,full_text) VALUES ($file_query,$fulltext_query)");
                $dbHandle->commit();
                $dbHandle = null;

                if (!$output)
                    $answer[] = 'Database error.';

                $output = null;
            } else {
                $answer[] = "There is no text to extract.";
            }
        } else {
            $answer[] = "Text extracting not allowed.";
        }
    } else {
        $answer[] = "File not found.";
    }

    $answers = join('<br>' . PHP_EOL, $answer);

    if (!empty($answers))
        echo $file_path . '<br><b>' . $answers . '</b><br>';

    set_time_limit(60);
}

echo '<br>All done.';

echo '</div>';
?>