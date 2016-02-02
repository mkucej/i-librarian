<?php
include_once 'data.php';
include_once 'functions.php';
include_once 'pdfclass.php';
session_write_close();

// Sanitize PDF filename.
$file = '';
if (!empty($_GET['file'])) {

    $file = preg_replace('/[^a-zA-z0-9\_\.pdf]/', '', $_GET['file']);
} else {

    displayError("No PDF provided.");
}

// Start the PDFViewer class.
$pdfHandler = new PDFViewer($file);

// Get page number and sizes.
$page_info = $pdfHandler->getPageInfo();

$page_number = $page_info['page_number'];
$page_sizes = $page_info['page_sizes'];

// Initial page number.
$page = $pdfHandler->getInitialPageNumber();

// Image resolutions in PPI.
$thumb_res = $pdfHandler->thumb_resolution;
$page_res = $pdfHandler->page_resolution;

// Display PDF file.
if (!isset($_GET['inline'])) {

    ?>

    <!DOCTYPE html>
    <html style="width:100%;height:100%">
        <head>
            <meta http-equiv="Content-Type" content="text/html; charset=UTF-8">
            <meta http-equiv="X-UA-Compatible" content="IE=edge">
            <link rel="shortcut icon" href="favicon.ico">
            <title>
    <?php print !empty($_GET['title']) ? 'PDF: ' . htmlspecialchars($_GET['title']) : 'PDF viewer'; ?>
            </title>
            <link type="text/css" href="css/custom-theme/jquery-ui-custom.min.css?v=<?php print $version ?>" rel="stylesheet">
            <link type="text/css" href="css/plugins.css?v=<?php print $version ?>" rel="stylesheet">
            <link type="text/css" href="css/font-awesome.css?v=<?php print $version ?>" rel="stylesheet">
            <link type="text/css" href="style.php?v=<?php print $version ?>" rel="stylesheet">
            <style type="text/css">
                @page {
                    margin: 0;
                }
            </style>
            <script type="text/javascript" src="js/jquery.js?v=<?php print $version ?>"></script>
            <script type="text/javascript" src="js/jquery-ui-custom.min.js?v=<?php print $version ?>"></script>
            <script type="text/javascript" src="js/plugins.js?v=<?php print $version ?>"></script>
        </head>
        <body style="padding:0;margin:0;border:0;overflow:hidden;width:100%;height:100%">

            <?php
        }

        ?>

        <div style="<?php
        if ((isset($_GET['toolbar']) && $_GET['toolbar'] == 0) || (isset($_GET['preview']) && $_GET['preview'] == 1)) {
            print 'display:none';
        }

        ?>" id="pdf-viewer-controls" class="alternating_row">
            <div class="pdf-viewer-control-row">
                <table>
                    <tr>
                        <td style="padding:2px 0 0 0;line-height:28px">
                            <div class="vertical-separator"></div>
                        </td>
                        <td style="padding:2px 0 0 4px;line-height:28px">
                            <button id="save" title="Download PDF"><i class="fa fa-download"></i></button>
                        </td>
                        <td style="padding:2px 0 0 4px;line-height:28px">
                            <div class="vertical-separator"></div>
                        </td>
                        <td style="padding:2px 0 0 4px;line-height:28px">
                            <button id="size1"><i class="fa fa-search-plus"></i> 100%</button>
                            <button id="size2" title="Fit the page width">|<i class="fa fa-arrows-h"></i>|</button>
                        </td>
                        <td style="padding-left:4px;padding-top:8px;line-height:28px">
                            <div id="zoom"></div><div style="float:left;position:relative;top:-4px;width:3em;text-align:right"></div>
                        </td>
                        <td style="padding:2px 0 0 4px;line-height:28px">
                            <div class="vertical-separator"></div>
                        </td>
                        <td style="padding:2px 0 0 4px;line-height:28px">
                            <button id="control-first" title="First page"><i class="fa fa-fast-backward"></i></button>
                            <button id="control-prev" title="Previous page (E)"><i class="fa fa-step-backward"></i></button>
                        </td>
                        <td style="padding:2px 0 0 4px;line-height:28px">
                            <input type="text" id="control-page" size="3" style="width:3em;padding:2px" value="<?php print intval($page) ?>"> / <?php print $page_number ?>
                        </td>
                        <td style="padding:2px 0 0 4px;line-height:28px">
                            <button id="control-next" title="Next page (D)"><i class="fa fa-step-forward"></i></button>
                            <button id="control-last" title="Last page"><i class="fa fa-fast-forward"></i></button>
                        </td>
                        <td style="padding:2px 0 0 4px;line-height:28px">
                            <div class="vertical-separator"></div>
                        </td>
                        <td style="padding:2px 0 0 4px;line-height:28px">
                            <button id="pdf-viewer-copy-image" title="Copy image" <?php
        if (!extension_loaded('gd'))
            print 'disabled'

            ?>>
                                <i class="fa fa-image"></i>
                            </button>
                            <input type="checkbox" id="pdf-viewer-copy-text">
                            <label for="pdf-viewer-copy-text" title="Copy text">
                                <i class="fa fa-font" style="padding:0 1px;border:1px dotted black"></i>
                            </label>
                        </td>
                    </tr>
                </table>
            </div>
            <div class="pdf-viewer-control-row">
                <table>
                    <tr>
                        <td style="padding:2px 0 0 0;line-height:28px">
                            <div class="vertical-separator"></div>
                        </td>
                        <td style="padding:2px 0 0 4px;line-height:28px">
                            <input type="checkbox" id="pageprev-button">
                            <label for="pageprev-button" title="Page previews"><i class="fa fa-file-text-o"></i></label>
                            <input type="checkbox" id="bookmarks-button">
                            <label for="bookmarks-button" title="Bookmarks"><i class="fa fa-bookmark"></i></label>
                            <input type="checkbox" id="notes-button">
                            <label for="notes-button" title="List notes"><i class="fa fa-th-list"></i></label>
                            <input type="checkbox" id="search-results-button">
                            <label for="search-results-button" title="Search results"><i class="fa fa-search"></i></label>
                        </td>
                        <td style="padding:2px 0 0 4px;line-height:28px">
                            <div class="vertical-separator"></div>
                        </td>
                        <td style="padding:2px 0 0 4px;line-height:28px">
                            <input type="checkbox" id="pdf-viewer-annotations">
                            <label for="pdf-viewer-annotations" title="Toggle annotations"><i class="fa fa-comment"></i></label>
                            <input type="checkbox" id="pdf-viewer-marker">
                            <label for="pdf-viewer-marker" title="Marker"><i class="fa fa-pencil"></i></label>
                            <input type="checkbox" id="pdf-viewer-note">
                            <label for="pdf-viewer-note" title="Pinned note"><i class="fa fa-thumb-tack"></i></label>
                            <input type="checkbox" id="pdf-viewer-marker-erase">
                            <label for="pdf-viewer-marker-erase" title="Erase annotations"><i class="fa fa-eraser"></i></label>
                            <input type="checkbox" id="pdf-viewer-others-annotations">
                            <label for="pdf-viewer-others-annotations" title="Others' annotations"><i class="fa fa-user"></i></label>
                            <div class="arrow-top"></div>
                            <div id="pdf-viewer-delete-menu" class="alternating_row" style="display:none">
                                <div>
                                    Erase selected
                                </div>
                                <div>
                                    Erase all markers
                                </div>
                                <div>
                                    Erase all notes
                                </div>
                                <div>
                                    Erase all
                                </div>
                            </div>
                        </td>
                        <td style="padding:2px 0 0 4px;line-height:28px">
                            <div class="vertical-separator"></div>
                        </td>
                        <td style="padding:2px 0 0 4px;line-height:28px">
                            <input type="text" id="pdf-viewer-search" value="" placeholder="Find" style="width:145px;padding:2px"
                                   title="Use &lt;?&gt; as single-letter, and &lt;*&gt; as multi-letter wildcards">
                        </td>
                        <td style="padding:2px 0 0 4px;line-height:28px">
                            <button id="pdf-viewer-clear" title="Clear search"><i class="fa fa-reply"></i></button>
                        </td>
                        <td style="padding:2px 0 0 4px;line-height:28px">
                            <button id="pdf-viewer-search-prev" title="Previous search result"><i class="fa fa-search"></i> <i class="fa fa-caret-up"></i></button>
                        </td>
                        <td style="padding:2px 0 0 4px;line-height:28px">
                            <button id="pdf-viewer-search-next" title="Next search result"><i class="fa fa-search"></i> <i class="fa fa-caret-down"></i></button>
                        </td>
                    </tr>
                </table>
            </div>
        </div>
        <div id="pdf-viewer-div">
            <div id="navpane" style="display:none">
                <div id="thumbs" style="display:none">
                    <?php
                    for ($i = 1; $i <= $page_number; $i++) {
                        $width = round($thumb_res * $page_sizes[$i - 1][0] / $page_res);
                        $height = round($thumb_res * $page_sizes[$i - 1][1] / $page_res);
                        echo "<div style=\"padding: 0.5em 0\">Page $i"
                        . "<div class=\"pdf-viewer-thumbs\" data-title=\"\" "
                        . "style=\"width:" . $width . "px;height:" . $height . "px\">"
                        . "<img id=\"img-thumb-$i\" data-page=\"$i\" src=\"img/ajaxloader.gif\""
                        . " style=\"margin-top:" . round(($height / 2) - 8) . "px\">"
                        . "</div></div>\n";
                    }

                    ?>
                </div>
                <div id="bookmarks" style="text-align:left;display:none">
                    <input type="text" placeholder="Search bookmarks" class="pdf_filter" style="width:176px;margin:4px 0 4px 6px">
                </div>
                <div id="annotations-left" style="text-align:left;display:none">
                    <input type="text" placeholder="Search notes" class="pdf_filter" style="width:176px;margin:4px 0 0 6px">
                    <button id="print-notes" title="Print notes" style="margin-left:6px;margin-top:4px"><i class="fa fa-print"></i></button>
                </div>
                <div id="search-results" style="text-align:left;display:none">
                    <div style="font-weight:bold;padding:6px 6px 0 6px">Search results:</div>
                </div>
            </div>
            <div id="pdf-viewer-img-div">
                <?php
                for ($i = 1; $i <= $page_number; $i++) {
                    echo "<div class=\"pdf-viewer-img\" id=\"pdf-viewer-img-{$i}\" "
                    . "style=\"background-color:white;width:{$page_sizes[$i - 1][0]}px;"
                    . "height:{$page_sizes[$i - 1][1]}px\" "
                    . "data-width=\"{$page_sizes[$i - 1][0]}\" "
                    . "data-height=\"{$page_sizes[$i - 1][1]}\">"
                    . "\n<div class=\"highlight-container\"></div>"
                    . "\n<div class=\"annotation-container\"></div>\n</div>\n";
                }

                ?>
                <div id="cursor">
                    <span class="fa"></span>
                </div>
            </div>
        </div>
        <div id="copy-image-container" style="display:none">
            <img src="" id="image-to-copy" style="box-shadow:0 0 2px rgba(0,0,0,0.33)">
            <form action="pdfcontroller.php" method="get">
                <input type="hidden" name="extractimage" value="1">
                <input type="hidden" name="file" value="<?php echo $file; ?>">
                <input type="hidden" name="image" id="image-src" value="">
                <input type="hidden" id="x" name="x">
                <input type="hidden" id="y" name="y">
                <input type="hidden" id="w" name="width">
                <input type="hidden" id="h" name="height">
                <input type="hidden" id="copy-image-mode" name="mode" value="save">
            </form>
        </div>
        <div id="copy-text-container" style="display:none"></div>
        <div id="save-container" title="Download options" style="display:none;padding-left:2em">
            <form action="pdfcontroller.php" method="get">
                <input type="hidden" name="downloadpdf" value="1">
                <input type="hidden" name="file" value="<?php print $file ?>">
                <input type="hidden" name="mode" value="download">
                <p>&nbsp;Attach to PDF:</p>
                <table>
                    <tr>
                        <td class="select_span">
                            <input type="checkbox" name="attachments[]" value="notes" style="display:none">
                            <i class="fa fa-square-o"></i>&nbsp;&nbsp;PDF notes and highlights
                        </td>
                    </tr>
                    <tr>
                        <td class="select_span" style="padding-left:18px">
                            <input type="checkbox" name="attachments[]" value="allusers" style="display:none">
                            <i class="fa fa-square-o"></i>&nbsp;&nbsp;from all users
                        </td>
                    </tr>
                    <tr>
                        <td class="select_span">
                            <input type="checkbox" name="attachments[]" value="supp" style="display:none">
                            <i class="fa fa-square-o"></i>&nbsp;&nbsp;supplementary files
                        </td>
                    </tr>
                    <tr>
                        <td class="select_span">
                            <input type="checkbox" name="attachments[]" value="richnotes" style="display:none">
                            <i class="fa fa-square-o"></i>&nbsp;&nbsp;rich-text notes
                        </td>
                    </tr>
                </table>
                <br>
            </form>
        </div>
        <div id="confirm-container" title="Confirm deletion" style="display:none"></div>
        <script type="text/javascript">
            var fileName = '<?php print $file ?>',
                    totalPages = <?php print $page_number ?>,
                    pg = <?php print $page ?>,
                    inline = false,
                    preview = false,
                    toolbar = true,
                    search_term = '';
<?php
if (isset($_GET['preview']) && $_GET['preview'] == 1) {
    echo "    preview = true;\n    toolbar = false;";
}
if (isset($_GET['toolbar']) && $_GET['toolbar'] == 0) {
    echo "    toolbar = false;";
}
if (isset($_GET['inline'])) {
    echo "    inline = true;";
}
if (!empty($_GET['search_term'])) {
    echo "    search_term = '$_GET[search_term]';";
}

?>
        </script>
        <script type="text/javascript" src="js/pdfviewer.js?v=<?php print $version ?>"></script>

<?php
if (!isset($_GET['inline'])) {

    ?>
        </body>
    </html>
    <?php
}

?>