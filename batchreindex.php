<?php
include_once 'data.php';
include_once 'functions.php';

session_write_close();

// Introduction.

if (!empty($_GET['intro'])) {
    ?>

    <div class="item-sticker ui-widget-content ui-corner-all" style="margin:auto;margin-top:100px;width:340px">
        <div class="ui-dialog-titlebar ui-state-default ui-corner-top" style="border:0;text-align:center">
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
$glob = new GlobIterator(IL_PDF_PATH . DIRECTORY_SEPARATOR . '[0-9]' . DIRECTORY_SEPARATOR . '[0-9]' . DIRECTORY_SEPARATOR . '*.pdf');

foreach ($glob as $pdf) {

    $answer = array();

    $file_path = $pdf->getPathname();
    $file_name = $pdf->getFilename();
    $file_id = intval(basename($pdf->getFilename(), '.pdf'));

    // Extract text from PDF.

    if (is_readable($file_path)) {

        $answer[] = recordFulltext($file_id, $file_name);

    } else {
        $answer[] = "File not found.";
    }

    $answer = array_filter($answer);

    $answers = join('<br>' . PHP_EOL, $answer);

    if (!empty($answers))
        echo $file_path . '<br><b>' . $answers . '</b><br>';

    set_time_limit(60);
}

echo '<br>All done.';

echo '</div>';
?>