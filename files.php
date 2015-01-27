<?php
include_once 'data.php';

if (isset($_SESSION['auth'])) {

    include_once 'functions.php';

    if (isset($_POST['filename']))
        $_GET['filename'] = $_POST['filename'];
    if (isset($_POST['file']))
        $_GET['file'] = $_POST['file'];

    ##########	read reference data	##########

    database_connect($database_path, 'library');

    $file_query = $dbHandle->quote($_GET['file']);

    $record = $dbHandle->query("SELECT id,file FROM library WHERE id=$file_query LIMIT 1");
    $paper = $record->fetch(PDO::FETCH_ASSOC);

    $record = null;
    $dbHandle = null;

    database_connect($database_path, 'fulltext');

    $file_query = $dbHandle->quote($_GET['file']);

    $record = $dbHandle->query("SELECT full_text FROM full_text WHERE fileID=$file_query");
    $fulltext = $record->fetchColumn();

    $record = null;
    $dbHandle = null;
    ?>
    <div id="preview" style="display:none;position:fixed;top:1px;right:1px;background-color:#CFCECC;z-index:100"></div>
    <table cellspacing="0" style="width:100%;height:100%;margin-top:0px">
        <tr>
            <td class="alternating_row" style="padding: 5px">
                <form id="uploadfiles" enctype="multipart/form-data" action="ajaxsupplement.php" method="POST">
                    <input type="hidden" name="file" value="<?php print htmlspecialchars($paper['id']) ?>">
                    <input type="hidden" name="filename" value="<?php print htmlspecialchars($paper['file']) ?>">
                    <button id="submituploadfiles"><i class="fa fa-save"></i> Save</button>
                    <div class="separator" style="margin:8px 0"></div>
                    <strong>Add/replace PDF:</strong><br>
                    Local file:<br>
                    <input type="file" name="form_new_file" accept="application/pdf"><br>
                    PDF from the Web:<br>
                    <input type="text" name="form_new_file_link" style="width: 99%"><br>
                    <div class="separator" style="margin:8px 0"></div>
                    <b>Add graphical abstract:</b><br>
                    <input type="file" name="form_graphical_abstract" accept="image/*"><br>
                    <div class="separator" style="margin:8px 0"></div>
                    <b>Add supplementary files:</b><br>
                    <input type="file" name="form_supplementary_file1"><br>
                    <input type="file" name="form_supplementary_file2"><br>
                    <input type="file" name="form_supplementary_file3"><br>
                    <input type="file" name="form_supplementary_file4"><br>
                    <input type="file" name="form_supplementary_file5"><br>
                </form>
            </td>
            <td style="width:90%;padding: 2px">
                <div style="border-bottom:1px solid #cfcecc;font-weight:bold">PDF file:</div>
                <?php if (file_exists("library/$paper[file]")) { ?>
                    <table border=0 cellspacing=0 cellpadding=0 style="width:100%;margin:0px">
                        <tr class="file-highlight" data-fileid="<?php print $_GET['file'] ?>">
                            <td style="height:22px;line-height:22px">
                                <i class="fa fa-file-pdf-o"></i>
                                <?php print $paper['file']; ?>
                            </td>

                            <?php
                            if (!empty($fulltext)) {
                                ?>
                                <td style="width:8em">
                                    <div class="ui-state-highlight" style="float:right;padding:1px 4px">
                                        <a href="viewindex.php?file=<?php print $_GET['file'] ?>" target="_blank" style="display:block;color:#000000">
                                            <i class="fa fa-file-text-o"></i> See Text
                                        </a>
                                    </div>
                                </td>
                                <td style="width:6.5em">
                                    <div class="ui-state-highlight reindex" id="reindex-<?php print $_GET['file'] ?>" style="float:right;padding:1px 4px">
                                        <i class="fa fa-refresh"></i> Reindex
                                    </div>
                                </td>
                                <?php
                            } else {
                                ?>
                                <td style="width:25em;text-align:right">
                                    No extractable text.
                                    <div class="ui-state-highlight ocr" style="float:right;padding:1px 4px;margin-left:0.5em">
                                        <i class="fa fa-file-text-o"></i> Try OCR
                                    </div>
                                    <div class="ui-state-highlight reindex" id="reindex-<?php print $_GET['file'] ?>" style="float:right;padding:1px 4px;margin-left:0.5em">
                                        <i class="fa fa-refresh"></i> Reindex
                                    </div>
                                </td>
                                <?php
                            }
                            ?>


                        </tr>
                    </table>
                <?php } ?>
                <form id="filesform" enctype="multipart/form-data" action="ajaxsupplement.php" method="POST">
                    <input type="hidden" name="file" value="<?php print htmlspecialchars($paper['id']) ?>">
                    <div id="filelist">
                        <div style="width:100%;margin:0px;border-bottom:1px solid #cfcecc;font-weight:bold">Graphical Abstract:</div>
                        <?php
                        $integer = sprintf("%05d", intval($paper['file']));
                        $gr_abs = glob("library/supplement/" . $integer . "graphical_abstract.*");
                        if (!empty($gr_abs[0])) {
                            $url_filename = htmlspecialchars(substr(basename($gr_abs[0]), 5));

                            print '<table style="width:100%">
                             <tr class="file-highlight" id="file' . htmlspecialchars(basename($gr_abs[0])) . '">
                              <td style="height:22px;line-height:22px"><i class="fa fa-image image"></i> 
                              <a class="image" href="' . htmlspecialchars('attachment.php?mode=inline&attachment=' . basename($gr_abs[0])) . '" target="_blank">' . $url_filename . '</a></td>
                              <td style="height:22px;line-height:22px">
                               <div class="ui-state-highlight file-remove" style="float:right;padding:1px 4px"><i class="fa fa-trash-o"></i> Remove</div>
                              </td>
                             </tr>
                            </table>';
                        }
                        ?>
                        <div style="width:100%;margin:0px;border-bottom:1px solid #cfcecc;font-weight:bold">Supplementary files:</div>
                        <table cellspacing=0 style="width:100%">
                            <tr><td></td><td></td><td></td></tr>
                            <?php
                            $files_to_display = glob('library/supplement/' . $integer . '*');

                            if (is_array($files_to_display)) {

                                foreach ($files_to_display as $supplementary_file) {

                                    $url_filename = substr(basename($supplementary_file), 5);

                                    if (strstr($url_filename, 'graphical_abstract') === false) {

                                        $extension = pathinfo($supplementary_file, PATHINFO_EXTENSION);

                                        $isimage = null;
                                        if ($extension == 'jpg' || $extension == 'jpeg' || $extension == 'gif' || $extension == 'png') {
                                            $image_array = array();
                                            $image_array = @getimagesize($supplementary_file);
                                            $image_mime = $image_array['mime'];
                                            if ($image_mime == 'image/jpeg' || $image_mime == 'image/gif' || $image_mime == 'image/png')
                                                $isimage = true;
                                        }

                                        $isaudio = null;
                                        if ($extension == 'ogg' || $extension == 'oga' || $extension == 'wav' || $extension == 'mp3' || $extension == 'm4a' || $extension == 'fla' || $extension == 'webma')
                                            $isaudio = true;

                                        $isvideo = null;
                                        if ($extension == 'ogv' || $extension == 'webmv' || $extension == 'm4v' || $extension == 'flv')
                                            $isvideo = true;

                                        print '<tr class="file-highlight" id="file' . htmlspecialchars(basename($supplementary_file)) . '">' . PHP_EOL;

                                        print '<td style="height:22px;line-height:22px;padding:1px 0">' . PHP_EOL;

                                        if ($isimage) {
                                            print '<i class="fa fa-image image"></i> ';
                                            print '<a href="' . htmlspecialchars('attachment.php?mode=inline&attachment=' . basename($supplementary_file)) . '" target="_blank">';
                                        } elseif ($isaudio) {
                                            print '<i class="fa fa-music audio" style="cursor:pointer" title="Click to play"></i> ';
                                            print '<a href="' . htmlspecialchars('attachment.php?attachment=' . basename($supplementary_file)) . '">';
                                        } elseif ($isvideo) {
                                            print '<i class="fa fa-film video" style="cursor:pointer" title="Click to play"></i> ';
                                            print '<a href="' . htmlspecialchars('attachment.php?attachment=' . basename($supplementary_file)) . '">';
                                        } else {
                                            print '<a href="' . htmlspecialchars('attachment.php?mode=inline&attachment=' . basename($supplementary_file)) . '"><i class="fa fa-file-o" style="padding:0.15em"></i></a> ';
                                            print '<a href="' . htmlspecialchars('attachment.php?attachment=' . basename($supplementary_file)) . '">';
                                        }

                                        print htmlspecialchars($url_filename) . '</a>' . PHP_EOL;

                                        print '<input class="rename_container" type="text" size="35" name="rename[' . htmlspecialchars(basename($supplementary_file)) . ']" value="' . htmlspecialchars($url_filename) . '" style="display:none;margin-top:1px;width:90%">' . PHP_EOL;

                                        print '</td>' . PHP_EOL;

                                        print '<td style="width:6.5em;height:22px">' . PHP_EOL;

                                        print '<div class="ui-state-highlight file-rename" style="float:right;padding:1px 4px"><i class="fa fa-pencil"></i> Rename</div>' . PHP_EOL;

                                        print '</td>' . PHP_EOL;

                                        print '<td style="width:6.5em;height:22px">' . PHP_EOL;

                                        print '<div class="ui-state-highlight file-remove" style="float:right;padding:1px 4px"><i class="fa fa-trash-o"></i> Remove</div>' . PHP_EOL;

                                        print '</td></tr>' . PHP_EOL;

                                        print '<tr><td colspan=3 style="text-align:center">' . PHP_EOL;

                                        if ($isvideo)
                                            print '<div class="videocontainer" style="text-align:center;display:none"></div>';

                                        if ($isaudio)
                                            print '<div class="audiocontainer" style="text-align:center;display:none"></div>';

                                        print '</td></tr>' . PHP_EOL;
                                    }
                                }
                            }
                            ?>
                        </table>
                    </div>
                </form>
            </td>
        </tr>
    </table>
    <?php
} else {
    print 'Super User or User permissions required.';
}
?>
