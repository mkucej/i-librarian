<?php
include_once 'data.php';
include_once '../functions.php';
session_write_close();

if (isset($_GET['id'])) {
    $id = intval($_GET['id']);
} else {
    die('Error! Missing item id.');
}

if (!isset($_SESSION['display'])) {
    $display = 'titles';
} else {
    $display = $_SESSION['display'];
}

database_connect($database_path, 'library');

$result = $dbHandle->query("SELECT id,file,authors,title,journal,year,abstract,secondary_title FROM library WHERE id=" . $id);
$item = $result->fetchAll();
$result = null;
if (!isset($item[0]))
    die('Error! Item does not exist.');
$paper = $item[0];

if (!empty($paper['authors'])) {
    $array = array();
    $new_authors = array();
    $array = explode(';', $paper['authors']);
    $array = array_filter($array);
    if (!empty($array)) {
        foreach ($array as $author) {
            $array2 = explode(',', $author);
            $last = trim($array2[0]);
            $last = substr($array2[0], 3, -1);
            $first = trim($array2[1]);
            $first = substr($array2[1], 3, -1);
            $new_authors[] = $last . ', ' . $first;
        }
        $paper['authors'] = join('; ', $new_authors);
    }
}

$paper['authors'] = htmlspecialchars($paper['authors']);
$paper['journal'] = htmlspecialchars($paper['journal']);
$paper['secondary_title'] = htmlspecialchars($paper['secondary_title']);
$paper['title'] = htmlspecialchars($paper['title']);
$paper['abstract'] = htmlspecialchars($paper['abstract']);
$paper['year'] = htmlspecialchars($paper['year']);

// AUTHOR FORMATTING
$first_author = '&nbsp;';
$auth_arr = explode(';', $paper['authors']);
$auth_arr2 = explode(',', $auth_arr[0]);
if (!empty($auth_arr2[0]))
    $first_author = $auth_arr2[0];
$etal = '';
if (count($auth_arr) > 1)
    $etal = ', et al.';

// DATE FORMATTING
$date = '';
if (!empty($paper['year'])) {
    $date_array = array();
    $date_array = explode('-', $paper['year']);
    if (count($date_array) == 1) {
        $date = $paper['year'];
    } else {
        if (empty($date_array[0]))
            $date_array[0] = '1969';
        if (empty($date_array[1]))
            $date_array[1] = '01';
        if (empty($date_array[2]))
            $date_array[2] = '01';
        $date = date('Y M j', mktime(0, 0, 0, $date_array[1], $date_array[2], $date_array[0]));
    }
}

if (isset($_SESSION['auth'])) {

    $result = $dbHandle->query("SELECT notesID,notes FROM notes WHERE fileID=" . $id);
    $fetched = $result->fetch(PDO::FETCH_ASSOC);
    $result = null;

    $paper['notesID'] = $fetched['notesID'];
    $paper['notes'] = $fetched['notes'];
}

if ($display == 'icons') {

    // NOT IMPLEMENTED
    
} else {

    // SUPPLEMENTARY FILE LIST
    $integer = sprintf("%05d", intval($paper['file']));
    $files_to_display = glob('../library/supplement/' . $integer . '*');
    $url_filename = '<div style="font-size:0.8em">No supplementary files.</div>';
    $url_filenames = array();
    if (is_array($files_to_display)) {
        foreach ($files_to_display as $supplementary_file) {
            $isimage = null;
            $image_array = array();
            $extension = pathinfo($supplementary_file, PATHINFO_EXTENSION);
            if ($extension == 'jpg' || $extension == 'jpeg' || $extension == 'gif' || $extension == 'png') {
                $image_array = @getimagesize($supplementary_file);
                $image_mime = $image_array['mime'];
                if ($image_mime == 'image/jpeg' || $image_mime == 'image/gif' || $image_mime == 'image/png')
                    $isimage = true;
            }
            if ($isimage)
                $url_filenames[] = '<li><a style="font-size:0.85em" href="' . htmlspecialchars($supplementary_file) . '" target="_blank">' . substr(basename($supplementary_file), 5) . '</a></li>';
            if (!$isimage)
                $url_filenames[] = '<li><a style="font-size:0.85em" href="' . htmlspecialchars('attachment.php?attachment=' . basename($supplementary_file)) . '" target="_blank">' . substr(basename($supplementary_file), 5) . '</a></li>';
        }
        $url_filename = join(PHP_EOL, $url_filenames);
        $url_filename = '<ul data-role="listview">' . $url_filename . '</ul>';
    }
    
    // TOP ROW - PDF BUTTON, CLIPBOARD BUTTON

    print '<table style="width:100%"><tr><td style="width:48%;padding-right:4px">';

    if (is_file('../library/' . $paper['file']) && isset($_SESSION['auth'])) {

        print '<a data-role="button" data-mini="true"
                href="' . htmlspecialchars('downloadpdf.php?file=' . urlencode($paper['file']) . '#pagemode=none&scrollbar=1&navpanes=0&toolbar=1&statusbar=0&page=1&view=FitH,0') . '" target="_blank">
                PDF</a>';
    } else {
        print '<button data-mini="true" disabled>PDF</button>';
    }

    print '</td><td style="padding-left:4px">';
       
    print '<form><input class="update_clipboard" name="checkbox-clipboard" id="checkbox-clipboard-' . $paper['id'] . '" type="checkbox" data-mini="true"';
    
    if (isset($_SESSION['session_clipboard']) && in_array($paper['id'], $_SESSION['session_clipboard'])) print ' checked="checked"';
                    
    print '><label for="checkbox-clipboard-' . $paper['id'] . '">Clipboard</label></form>';

    print '</td></tr></table>';
    
    // SECOND ROW - TITLE, JOURNAL, YEAR, AUTHOR

    print PHP_EOL . '<ul data-role="listview" data-inset="true"><li style="word-wrap:break-word;font-size:0.8em">' . $paper['title'] . '<span style="font-weight:normal">';

    if (!empty($paper['authors']))
        print PHP_EOL . $first_author . $etal . ' ';

    print (!empty($paper['journal']) ? $paper['journal'] : $paper['secondary_title']);

    print (!empty($date)) ? ' (' . $date . ')' : '';

    print '</span></li></ul>';
    
    // THIRD ROW, ABSTRACT, NOTES, SUPPLEMENTARY FILES

    print '<div data-role="collapsible-set" data-theme="a" data-content-theme="a" style="margin:8px 0" class="item-accordeon">
                <div data-role="collapsible">
                    <h3 class="accordeon">Abstract</h3>
                    <div style="font-size:0.8em">' . (empty($paper['abstract']) ? 'No abstract.' : $paper['abstract']) . '</div>
                </div>
                <div data-role="collapsible">
                    <h3 class="accordeon">Notes</h3>
                    <div style="font-size:0.8em">' . (empty($paper['notes']) ? 'No notes.' : $paper['notes']) . '</div>
                </div>
                <div data-role="collapsible">
                    <h3 class="accordeon">Supplementary files</h3>' . $url_filename . '
                </div>
            </div>';
}
?>