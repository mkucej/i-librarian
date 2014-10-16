<?php
include_once 'data.php';
include_once 'functions.php';

if (isset($_GET['file']))
    $_GET['file'] = intval($_GET['file']);

if (isset($_GET['delete']) && isset($_GET['file']) && isset($_SESSION['permissions']) && $_SESSION['permissions'] == 'A') {
    database_connect($database_path, 'library');
    $error = null;
    $error = delete_record($dbHandle, $_GET['file']);
    die($error);
}

if (isset($_GET['neighbors']) && isset($_GET['file'])) {

    $export_files = read_export_files(0);

    $current_record = array_search($_GET['file'], $export_files);
    isset($export_files[$current_record - 1]) ? $prevrecord = $export_files[$current_record - 1] : $prevrecord = 'none';
    isset($export_files[$current_record + 1]) ? $nextrecord = $export_files[$current_record + 1] : $nextrecord = 'none';
    die($prevrecord . ':' . $nextrecord);
}

if (isset($_GET['checkitem']) && isset($_GET['files'])) {

    $diff = array();
    $export_files = read_export_files(0);
    $diff = array_diff((array) $_GET['files'], (array) $export_files);
    echo json_encode($diff);
    die();
}

session_write_close();

$export_files = read_export_files(0);
if (empty($export_files)) {
    //HACK, SOMETIMES CLIENT IS REFRESHING EXPORT FILES
    for ($i = 1; $i <= 10; $i++) {
        if (empty($export_files)) {
            sleep(1);
            $export_files = read_export_files(0);
        } else {
            break;
        }
    }
}
if (empty($export_files))
    die('Error! No files to display.');
?>
<div id="items-left" class="noprint alternating_row" style="position:relative;float:left;width:233px;height:100%;overflow:scroll;border:0;margin:0">
    <?php
    if (empty($_GET['file']))
        $_GET['file'] = $export_files[0];
    $key = array_search(intval($_GET['file']), $export_files);
    $offset = max($key - 9, 0);
    if ($offset > count($export_files) - 20) {
        $offset = max(count($export_files) - 20, 0);
    }
    $show_items = array_slice($export_files, $offset, 20);
    if ($offset > 0) {
        print '<div class="ui-state-highlight lib-shadow-bottom" style="margin-bottom:4px;height:17px" title="Previous">';
        print '<a href="items.php?file=' . $export_files[$offset - 1] . '" class="navigation" style="display:block;width:100%" id="' . $export_files[$offset - 1] . '">';
        print '<i class="fa fa-caret-up"></i>';
        print '</a></div>';
    }
    print '<div id="list-title-copy" class="items" style="font-weight:bold"></div><div class="separator"></div>';

    $divs = array();

    database_connect($database_path, 'library');

    $query = join(",", $show_items);
    $result = $dbHandle->query("SELECT id,file,title FROM library WHERE id IN (" . $query . ")");
    $dbHandle = null;
    $result = $result->fetchAll(PDO::FETCH_ASSOC);

    //SORT QUERY RESULTS
    $tempresult = array();
    foreach ($result as $row) {
        $key = array_search($row['id'], $show_items);
        $tempresult[$key] = $row;
    }
    ksort($tempresult);
    $result = $tempresult;

    foreach ($result as $item) {

        $divs[] = '<div id="list-item-' . $item['id'] . '" data-id="' . $item['id'] . '" data-file="' . $item['file'] . '" class="items listleft">' .
                lib_htmlspecialchars($item['title']) .
                '</div>';
    }
    $result = null;

    if (isset($_GET['file'])) {
        $current_record = array_search($_GET['file'], $export_files);
        isset($export_files[$current_record - 1]) ? $prevrecord = $export_files[$current_record - 1] : $prevrecord = null;
        isset($export_files[$current_record + 1]) ? $nextrecord = $export_files[$current_record + 1] : $nextrecord = null;
    }

    $hr = '<div class="separator"></div>';

    print join($hr, $divs);

    if ($offset < count($export_files) - 20) {
        print '<div class="ui-state-highlight lib-shadow-top" style="margin-top:4px" title="Next">';
        print '<a href="items.php?file=' . $export_files[$offset + 20] . '" class="navigation" style="display:block;width:100%" id="file-' . $export_files[$offset + 20] . '">';
        print '<i class="fa fa-caret-down"></i>';
        print '</a></div>';
    }
    ?>
</div>
<div class="alternating_row middle-panel"
     style="float:left;width:6px;height:100%;overflow:hidden;border-right:1px solid rgba(0,0,0,0.2);border-left:1px solid rgba(0,0,0,0.2);cursor:pointer">
    <i class="fa fa-caret-left" style="position:relative;left:1px;top:48%"></i>
</div>
<div style="width:auto;height:100%;overflow:hidden" id="items-right" data-file="<?php echo $_GET['file'] ?>">
    <?php
    if (!empty($_GET['file'])) {
        ?>
        <div class="noprint ui-state-highlight" id="items-menu">
            <div class="backbutton" title="Back to list view (Q)">
                <i class="ui-state-error-text fa fa-times-circle"></i><br> Close
            </div>
            <div title="Previous Item (W)" class="prevrecord <?php print empty($prevrecord) ? ' ui-state-disabled' : ''  ?>" id="prev-item-<?php print $prevrecord ?>">
                <i class="fa fa-chevron-circle-up"></i><br>Prev
            </div>
            <div title="Next Item (S)" class="nextrecord <?php print empty($nextrecord) ? ' ui-state-disabled' : ''  ?>" id="next-item-<?php print $nextrecord ?>">
                <i class="fa fa-chevron-circle-down"></i><br>Next
            </div>
            <div class="tab" id="file-item">
                <i class="fa fa-home"></i><br>Item
            </div>
            <div class="tab" id="file-pdf">
                <i class="fa fa-file-pdf-o"></i><br>PDF
            </div>
            <div class="tab" id="file-notes">
                <i class="fa fa-pencil"></i><br>Notes
            </div>
            <div class="tab" id="file-files">
                <i class="fa fa-paperclip"></i><br>Files
            </div>
            <div class="tab" id="file-categories">
                <i class="fa fa-tags"></i><br>Categ.
            </div>
            <?php
            if ($_SESSION['permissions'] != 'G') {
                ?>
                <div class="tab" id="file-edit">
                    <i class="fa fa-cog"></i><br>Edit
                </div>
                <?php
            }
            ?>
            <div class="tab" id="file-discussion">
                <i class="fa fa-comments-o"></i><br>Discuss
            </div>
            <div id="exportfilebutton">
                <i class="fa fa-briefcase"></i><br>Export
            </div>
            <div id="emailbutton">
                <a href="" target="_blank" style="display:inline-block;color:inherit">
                    <i class="fa fa-envelope-o"></i><br>E-Mail
                </a>
            </div>
            <div id="printbutton">
                <i class="fa fa-print"></i><br>Print
            </div>
            <?php
            if (isset($_SESSION['auth'])) {
                if ($_SESSION['permissions'] == 'A') {
                    ?>
                    <div id="deletebutton" title="Permanently delete this record (Del)">
                        <i class="fa fa-trash-o"></i><br>Delete
                    </div>
                    <?php
                }
            }
            ?>
        </div>
        <div id="items-pdf-menu" style="display:none">
            <?php
            if (isset($_SESSION['pdfviewer']) && $_SESSION['pdfviewer'] == 'external')
                print '<i class="fa fa-external-link"></i> <a href="' . htmlspecialchars('downloadpdf.php?file=' . urlencode($paper['file']) . '#pagemode=none&scrollbar=1&navpanes=0&toolbar=1&statusbar=0&page=1&view=FitH,0') . '" target="_blank" class="pdf_link">
				<span class="ui-state-highlight" style="padding:0px 2px 0px 2px;margin-right:2px">Open PDF in new window</span></a>';

            if (!isset($_SESSION['pdfviewer']) || (isset($_SESSION['pdfviewer']) && $_SESSION['pdfviewer'] == 'internal'))
                print '<i class="fa fa-external-link"></i> <a href="' . htmlspecialchars('viewpdf.php?file=' . urlencode($item['file']) . '&title=' . urlencode($paper['title'])) . '" target="_blank" class="pdf_link">
				<span class="ui-state-highlight ui-corner-all" style="padding:0px 2px 0px 2px;margin-right:2px">Open PDF in new window</span></a>';
            ?>
        </div>
        <div id="file-panel" style="width:auto;height:48%;border-top:1px solid #c6c8cc;overflow:auto">
        </div>
        <?php
    } else {
        print '<h3>&nbsp;Error! This item does not exist.<br>&nbsp;Reload of the library is recommended.</h3>';
    }
    ?>
</div>