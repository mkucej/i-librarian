<?php
include_once 'data.php';
include_once 'functions.php';

$text = '';
$filename = sprintf('%05d', intval($_GET['file'])) . '.pdf';

if (!empty($_GET['file'])) {
    database_connect(IL_DATABASE_PATH, 'fulltext');
    $query = $dbHandle->quote($_GET['file']);
    $result = $dbHandle->query("SELECT full_text FROM full_text WHERE fileID=$query");
    $dbHandle = null;
    $text = $result->fetchColumn();
}

if (!empty($text)) {
    $text_size = round(strlen(utf8_decode($text)) / 1024, 1);
    $pdf_size = round(filesize(IL_PDF_PATH . DIRECTORY_SEPARATOR . get_subfolder($filename) . DIRECTORY_SEPARATOR . $filename) / 1024, 1);
} else {
    print '<h3>No text found.</h3>';
    die();
}
include_once 'index.inc.php';
?>
<body style="padding:20px">
    <h3>Extracted text from the file <?php print $filename; ?>:</h3>
    <div class="ui-corner-all alternating_row" style="float:left;width:75%;text-align:justify;padding:10px;text-shadow:none">
        <?php print htmlspecialchars($text); ?>
    </div>
    <div class="ui-corner-all alternating_row" style="float:right;width:20%;text-align:left;padding:10px;overflow:hidden;text-shadow:none">
        <table>
            <tr><td><b>Text Size:</b></td><td><?php print $text_size . ' kB'; ?></td></tr>
            <tr><td><b>PDF Size:</b></td><td><?php print $pdf_size . ' kB' ?></td></tr>
            <tr><td><b>Ratio:&nbsp;</b></td><td><?php print round(100 * (1 - ($text_size / $pdf_size)), 1) . '%'; ?></td></tr>
        </table>
    </div>
    <div style='clear:both'></div>
</body>
</html>