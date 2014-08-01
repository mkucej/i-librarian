<?php
include_once 'data.php';
include_once 'functions.php';
session_write_close();

//CROP IMAGE USING GD
if (!empty($_GET['cropimage'])) {

    if (!extension_loaded('gd'))
        die('PHP GD extension not installed.');

    parse_str(parse_url($_GET['image'], PHP_URL_QUERY), $src);
    $src = $library_path . 'pngs' . DIRECTORY_SEPARATOR . preg_replace("/[^a-z0-9\.]/", "", $src['png']);

    if (!is_file($src))
        die('Invalid input.');

    $w = $_GET['width'];
    $h = $_GET['height'];
    $x = $_GET['x'];
    $y = $_GET['y'];

    if (strpos($_GET['image'], $url) !== 0)
        die('Invalid image.');
    if ($_GET['width'] > 10000)
        die('Invalid input.');
    if ($_GET['height'] > 10000)
        die('Invalid input.');
    if ($_GET['x'] < 0 || $_GET['x'] > 10000)
        die('Invalid input.');
    if ($_GET['y'] < 0 || $_GET['y'] > 10000)
        die('Invalid input.');

    $img_array = getimagesize($src);

    if ($img_array['mime'] == 'image/png') {
        $img_r = imagecreatefrompng($src);
    } elseif ($img_array['mime'] == 'image/jpg' || $img_array['mime'] == 'image/jpeg') {
        $img_r = imagecreatefromjpeg($src);
    } else {
        die('Invalid input.');
    }

    $dst_r = imagecreatetruecolor($w, $h);

    imagecopy($dst_r, $img_r, 0, 0, $x, $y, $w, $h);

    $img_copy = imagecreatetruecolor($w, $h);
    imagecopy($img_copy, $img_r, 0, 0, $x, $y, $w, $h);
    imagetruecolortopalette($img_copy, false, 256);
    if (imagecolorstotal($img_copy) < 256) {
        imagetruecolortopalette($dst_r, false, 256);
    }

    header('Content-type: image/png');
    header("Content-Disposition: attachment; filename=image.png");
    header("Pragma: no-cache");
    header("Expires: 0");

    imagepng($dst_r, null, 6);

    imagedestroy($dst_r);
    imagedestroy($img_r);

    die();
}

$pdf_path = dirname(__FILE__) . DIRECTORY_SEPARATOR . 'library';
$png_path = dirname(__FILE__) . DIRECTORY_SEPARATOR . 'library' . DIRECTORY_SEPARATOR . 'pngs';

if (!empty($_GET['file'])) {
    $file = preg_replace('/[^a-zA-z0-9\_\.pdf]/', '', $_GET['file']);
    if (substr($_GET['file'], 0, 4) == 'lib_') {
        $pdf_path = $temp_dir;
    }
} else {
    die('Error! PDF does not exist.');
}

$page = 1;
if (isset($_GET['page'])) {
    $page = intval($_GET['page']);
} else {
    $userID = intval($_SESSION['user_id']);
    database_connect($database_path, 'history');
    $result = $dbHandle->query("SELECT page FROM bookmarks WHERE userID=$userID AND file='$file'");
    if (is_object($result))
        $page = $result->fetchColumn();
    if (!$page)
        $page = 1;
    $dbHandle = null;
}

if (file_exists($pdf_path . DIRECTORY_SEPARATOR . $file)) {
    exec(select_pdfinfo() . '"' . $pdf_path . DIRECTORY_SEPARATOR . $file . '"', $output);
    $output = implode('#', $output);
    $page_number = preg_replace('/(.*#Pages:\s+)(\d+)(#.*)/', '$2', $output);
    if ($page > $page_number)
        $page = $page_number;
    if (empty($page_number))
        die('Error! Program pdfinfo not functional.');
}

if (isset($_GET['renderpdf'])) {

    if (file_exists($pdf_path . DIRECTORY_SEPARATOR . $file)) {

        if (!file_exists($png_path . DIRECTORY_SEPARATOR . $file . '.' . $page . '.png') || filemtime($png_path . DIRECTORY_SEPARATOR . $file . '.' . $page . '.png') < filemtime($pdf_path . DIRECTORY_SEPARATOR . $file)) {

            exec(select_ghostscript() . " -dSAFER -dBATCH -dNOPAUSE -sDEVICE=png16m -r150 -dTextAlphaBits=4 -dGraphicsAlphaBits=4 -dDOINTERPOLATE -dFirstPage=" . $page . " -dLastPage=" . $page . " -o \"" . $png_path . DIRECTORY_SEPARATOR . $file . "." . $page . ".png\" \"" . $pdf_path . DIRECTORY_SEPARATOR . $file . "\"");

            if (file_exists($png_path . DIRECTORY_SEPARATOR . $file . '.' . $page . '.png')) {

                $img_size_array = getimagesize('library' . DIRECTORY_SEPARATOR . 'pngs' . DIRECTORY_SEPARATOR . $file . "." . $page . ".png");

                $w = $img_size_array[0];
                $h = $img_size_array[1];

                $img_r = imagecreatefrompng($png_path . DIRECTORY_SEPARATOR . $file . "." . $page . ".png");
                $img_copy = imagecreatetruecolor($w, $h);

                imagecopy($img_copy, $img_r, 0, 0, 0, 0, $w, $h);
                imagetruecolortopalette($img_copy, false, 256);

                if (imagecolorstotal($img_copy) < 256) {
                    imagetruecolortopalette($img_r, false, 256);
                    imagepng($img_r, $png_path . DIRECTORY_SEPARATOR . $file . "." . $page . ".png", 6);
                } elseif ($hosted == true) {
                    imagejpeg($img_r, $temp_dir . DIRECTORY_SEPARATOR . $file . "." . $page . ".png", 80);
                    if (filesize($png_path . DIRECTORY_SEPARATOR . $file . "." . $page . ".png") > filesize($temp_dir . DIRECTORY_SEPARATOR . $file . "." . $page . ".png")) {
                        copy($temp_dir . DIRECTORY_SEPARATOR . $file . "." . $page . ".png", $png_path . DIRECTORY_SEPARATOR . $file . "." . $page . ".png");
                    }
                }

                imagedestroy($img_r);
                imagedestroy($img_copy);
            } else {
                die('Error! Conversion with Ghostscript failed.');
            }
        }
        if (file_exists($png_path . DIRECTORY_SEPARATOR . $file . "." . $page . ".png")) {

            // bookmark open page
            if (substr($_GET['file'], 0, 4) != 'lib_') {

                $userID = intval($_SESSION['user_id']);

                database_connect($database_path, 'history');

                $dbHandle->exec("CREATE TABLE IF NOT EXISTS bookmarks (
                    id INTEGER PRIMARY KEY,
                    userID INTEGER NOT NULL DEFAULT '',
                    file TEXT NOT NULL DEFAULT '',
                    page INTEGER NOT NULL DEFAULT 1,
                    UNIQUE(userID,file)
                    )");

                $dbHandle->beginTransaction();
                $dbHandle->exec("DELETE FROM bookmarks WHERE userID=$userID AND file='$file'");
                if ($page > 1)
                    $dbHandle->exec("INSERT INTO bookmarks (userID,file,page) VALUES ($userID,'$file',$page)");
                $dbHandle->commit();
                $dbHandle = null;
            }

            // send image size to js
            $img_size_array = getimagesize('library' . DIRECTORY_SEPARATOR . 'pngs' . DIRECTORY_SEPARATOR . $file . "." . $page . ".png");
            print json_encode(array_slice($img_size_array, 0, 2));
        } else {
            die('Error! Conversion with Ghostscript failed.');
        }
    } else {
        die('PDF does not exist.');
    }
    die();
}

if (isset($_GET['renderthumbs'])) {

    if (file_exists($pdf_path . DIRECTORY_SEPARATOR . $file)) {

        if (!file_exists($png_path . DIRECTORY_SEPARATOR . $file . ".t1.png") || filemtime($png_path . DIRECTORY_SEPARATOR . $file . '.t1.png') < filemtime($pdf_path . DIRECTORY_SEPARATOR . $file)) {
            exec(select_ghostscript() . " -dSAFER -sDEVICE=png256 -r20 -dTextAlphaBits=1 -dGraphicsAlphaBits=1 -o \"" . $png_path . DIRECTORY_SEPARATOR . $file . ".t%d.png\" \"" . $pdf_path . DIRECTORY_SEPARATOR . $file . "\"");
        }
    }
    die();
}

if (isset($_GET['renderbookmarks'])) {

    if (file_exists($pdf_path . DIRECTORY_SEPARATOR . $file)) {

        $safe_file_name = preg_replace('/[^\d\.pdf]/', '', $_GET['file']);
        $file_name = $pdf_path . DIRECTORY_SEPARATOR . $safe_file_name;
        $temp_file = $temp_dir . DIRECTORY_SEPARATOR . $safe_file_name . '-bookmarks.txt';
        if (!file_exists($temp_file) || filemtime($temp_file) < filemtime($file_name))
            system(select_pdftk() . '"' . $file_name . '" dump_data output "' . $temp_file . '"', $ret);

        if (file_exists($temp_file)) {
            $i = 0;
            $bookmark = array();
            $pdftk_array = file($temp_file, FILE_IGNORE_NEW_LINES);
            foreach ($pdftk_array as $pdftk_line) {
                if (stripos($pdftk_line, 'BookmarkTitle') === 0) {
                    $bookmark[$i]['title'] = trim(stristr($pdftk_line, ' '));
                    $j = $i;
                }
                if (stripos($pdftk_line, 'BookmarkLevel') === 0)
                    $bookmark[$j]['level'] = trim(stristr($pdftk_line, ' '));
                if (stripos($pdftk_line, 'BookmarkPageNumber') === 0) {
                    $bookmark[$j]['page'] = trim(stristr($pdftk_line, ' '));
                    if ($bookmark[$j]['page'] == 0)
                        unset($bookmark[$j]);
                }
                $i++;
            }
            $bookmark = array_values($bookmark);
            die(json_encode($bookmark));
        }
    }

    die();
}

if (!isset($_GET['inline'])) {
    ?>

    <!DOCTYPE html>
    <html style="width:100%;height:100%">
        <head>
            <meta http-equiv="Content-Type" content="text/html; charset=UTF-8">
            <link rel="shortcut icon" href="red.ico">
            <title>
    <?php print !empty($_GET['title']) ? 'PDF: ' . $_GET['title'] : 'PDF viewer'; ?>
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
             if (isset($_GET['toolbar']) && $_GET['toolbar'] == 0)
                 print 'display:none';
             ?>" id="pdf-viewer-controls" class="alternating_row">
            <div class="pdf-viewer-control-row">
                <table>
                    <tr>
                        <td style="padding:2px 0 0 4px;line-height:28px">
                            <button id="save" title="Download PDF"><i class="fa fa-download"></i></button>
                        </td>
                        <td style="padding:2px 0 0 4px;line-height:28px">
                            <div class="vertical-separator"></div>
                        </td>
                        <td style="padding:2px 0 0 4px;line-height:28px">
                            <button id="size1"><i class="fa fa-search-plus"></i> 100%</button>
                            <button id="size2" title="Fit the page width">|<i class="fa fa-arrows-h"></i>|</button>
                            <button id="size3" title="Fit the page height"><i class="fa fa-file-o"></i> <i class="fa fa-arrows-v"></i></button>
                        </td>
                        <td style="padding-left:4px;padding-top:8px;line-height:28px">
                            <div id="zoom" style="margin-top:4px"></div><div style="float:left;position:relative;top:-4px;width:3em;text-align:right"></div>
                        </td>
                        <td style="padding:2px 0 0 4px;line-height:28px">
                            <div class="vertical-separator"></div>
                        </td>
                        <td style="padding:2px 0 0 4px;line-height:28px">
                            <button id="control-first" title="First page"><i class="fa fa-angle-double-up"></i></button>
                            <button id="control-prev" title="Previous page (E)"><i class="fa fa-angle-up"></i></button>
                        </td>
                        <td style="padding:2px 0 0 4px;line-height:28px">
                            <input type="text" id="control-page" size="3" style="width:3em;padding:2px" value="<?php print intval($page) ?>"> / <?php print $page_number ?>
                        </td>
                        <td style="padding:2px 0 0 4px;line-height:28px">
                            <button id="control-next" title="Next page (D)"><i class="fa fa-angle-down"></i></button>
                            <button id="control-last" title="Last page"><i class="fa fa-angle-double-down"></i></button>
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
            <div class="separator" style="margin:0"></div>
            <div class="pdf-viewer-control-row">
                <table>
                    <tr>
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
                            <div id="pdf-viewer-delete-menu" class="ui-corner-all alternating_row" style="display:none">
                                <div>
                                    Erase individually
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
                            <input type="text" id="pdf-viewer-search" size="10" value="" placeholder="Find" style="width:180px;padding:2px"
                                   title="Use &lt;?&gt; as single-letter, and &lt;*&gt; as multi-letter wildcards">
                        </td>
                        <td style="padding:2px 0 0 4px;line-height:28px">
                            <button id="pdf-viewer-clear" title="Clear"><i class="fa fa-reply"></i></button>
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
                <div id="thumbs" style="display:none"><p>Loading previews...</p></div>
                <div id="bookmarks" style="text-align:left;display:none"></div>
                <div id="annotations-left" style="text-align:left;display:none">
                    <input type="text" placeholder="Search notes" id="filter_notes" style="width:180px;margin-left:6px;margin-top:4px">
                    <button id="print-notes" title="Print notes" style="margin-left:6px;margin-top:4px"><i class="fa fa-print"></i></button>
                </div>
                <div id="search-results" style="text-align:left;display:none">
                    <div style="font-weight:bold;padding:6px 6px 0 6px">Search results:</div>
                </div>
            </div>
            <div id="pdf-viewer-img-div">
                <div id="pdf-viewer-img"></div>
                <div id="highlight-container"></div>
                <div id="annotation-container" style="display:none;"></div>
                <div id="cursor">
                    <span class="fa"></span>
                </div>
            </div>
        </div>
        <div id="copy-image-container" style="display:none">
            <img src="" id="image-to-copy" style="box-shadow:0 0 2px #333">
            <form action="viewpdf.php" method="get">
                <input type="hidden" name="cropimage" value="1">
                <input type="hidden" name="image" id="image-src" value="">
                <input type="hidden" id="x" name="x">
                <input type="hidden" id="y" name="y">
                <input type="hidden" id="w" name="width">
                <input type="hidden" id="h" name="height">
            </form>
        </div>
        <div id="copy-text-container" style="display:none"></div>
        <div id="save-container" title="Download options" style="display:none;padding-left:2em">
            <form action="viewpdf.php" method="get">
                <p>&nbsp;Attach to PDF:</p>
                <table>
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
                </table>
                <br>
            </form>
        </div>
        <div id="confirm-container" title="Confirm deletion" style="display:none"></div>
        <script type="text/javascript">
            var fileName = '<?php print $file ?>',
                    totalPages =<?php print $page_number ?>,
                    pg =<?php print $page ?>,
                    navpanes = false,
                    preview = false;
<?php
if (isset($_GET['navpanes']) && $_GET['navpanes'] == 1)
    print 'navpanes=true;';
if (isset($_GET['preview']) && $_GET['preview'] == 1)
    print 'preview=true;';
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