<?php
include_once 'data.php';
include_once 'functions.php';

$text = '';
$filename = sprintf('%05d', intval($_GET['file'])) . '.pdf';

if (!empty($_GET['file'])) {
    database_connect($database_path, 'fulltext');
    $query = $dbHandle->quote($_GET['file']);
    $result = $dbHandle->query("SELECT full_text FROM full_text WHERE fileID=$query LIMIT 1");
    $dbHandle = null;
    $text = $result->fetchColumn();
}

if (!empty($text)) {
    $text_size = round(strlen(utf8_decode($text)) / 1024, 1);
    $pdf_size = round(filesize("library/" . $filename) / 1024, 1);
    $text2 = preg_replace('/\s/ui', '<br>', $text);
    $text_words_array = explode("<br>", $text2);
    $text_words_array = array_filter($text_words_array, 'htmlspecialchars');
    $text_words_array = array_unique($text_words_array);
    natcasesort($text_words_array);
    $text_words = implode("<br>" . PHP_EOL, $text_words_array);
} else {
    print '<h3>No text found.</h3>';
    die();
}
include_once 'index.inc.php';
?>
<body class="ui-state-highlight" style="padding:20px;text-align:left">
    <h2>Extracted text from the file <?php print $filename; ?>:</h2>
    <div class="ui-corner-all alternating_row" style="float:left;width:75%;text-align:justify;padding:10px;text-shadow:none">
        <?php print htmlspecialchars($text); ?>
    </div>
    <div class="ui-corner-all alternating_row" style="float:right;width:20%;text-align:left;padding:10px;overflow:hidden;text-shadow:none">
        <table>
            <tr><td><b>Text Size:</b></td><td><?php print $text_size . ' kB'; ?></td></tr>
            <tr><td><b>PDF Size:</b></td><td><?php print $pdf_size . ' kB' ?></td></tr>
            <tr><td><b>Compression:&nbsp;</b></td><td><?php print round(100 * (1 - ($text_size / $pdf_size)), 1) . '%'; ?></td></tr>
        </table>
        <table>
            <tr><td><br><b>Sorted Words (<?php print count($text_words_array); ?>):</b></td></tr>
            <tr><td style="padding-left:10px"><?php print $text_words; ?></td></tr>
        </table>
    </div>
    <div style="clear:both">&nbsp;</div>
</body>
</html>