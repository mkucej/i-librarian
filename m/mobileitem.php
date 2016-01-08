<?php
include_once '../data.php';
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

database_connect(IL_DATABASE_PATH, 'library');

$result = $dbHandle->query("SELECT id,file,authors,title,journal,year,abstract,secondary_title FROM library WHERE id=" . $id);
$paper = $result->fetch(PDO::FETCH_ASSOC);
$result = null;
if (!$paper)
    die('Error! Item does not exist.');

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
            $first = '';
            if (isset($array2[1])) {
                $first = trim($array2[1]);
                $first = substr($array2[1], 3, -1);
            }
            $new_authors[] = $last . ', ' . $first;
        }
        $paper['authors'] = join('; ', $new_authors);
    }
}

// Read clipboard files.
attach_clipboard($dbHandle);
$clip_result = $dbHandle->query("SELECT id FROM clipboard.files WHERE id=$id");
$clip_files = $clip_result->fetchColumn();
$clip_result = null;

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
        $date = date('Y', mktime(0, 0, 0, $date_array[1], $date_array[2], $date_array[0]));
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
    $files_to_display = glob(IL_SUPPLEMENT_PATH . DIRECTORY_SEPARATOR . get_subfolder($integer) . DIRECTORY_SEPARATOR . $integer . '*');
    $url_filename = '<div style="font-size:0.8em">No supplementary files.</div>';
    $url_filenames = array();
    if (is_array($files_to_display)) {
        foreach ($files_to_display as $supplementary_file) {
            $url_filenames[] = '<li><a style="font-size:0.85em" href="' . htmlspecialchars('attachment.php?attachment=' . basename($supplementary_file)) . '" target="_blank">' . substr(basename($supplementary_file), 5) . '</a></li>';
        }
        $url_filename = join(PHP_EOL, $url_filenames);
        $url_filename = '<ul data-role="listview">' . $url_filename . '</ul>';
    }
    
    // TOP ROW - PDF BUTTON, CLIPBOARD BUTTON

    print '<table style="width:100%"><tr><td style="width:50%">';

    if (is_file(IL_PDF_PATH . DIRECTORY_SEPARATOR . get_subfolder($integer) . DIRECTORY_SEPARATOR . $paper['file']) && isset($_SESSION['auth'])) {

        print '<a class="ui-btn ui-mini ui-corner-all"
                href="' . htmlspecialchars('pdfcontroller.php?downloadpdf=1&file=' . urlencode($paper['file']) . '#pagemode=none&scrollbar=1&navpanes=0&toolbar=1&statusbar=0&page=1&view=FitH,0') . '" target="_blank">
                PDF</a>';
    } else {
        print '<button class="ui-btn ui-mini ui-corner-all" disabled>PDF</button>';
    }

    print '</td><td>';
       
    print '<form><input class="update_clipboard" name="checkbox-clipboard" id="checkbox-clipboard-' . $paper['id'] . '" type="checkbox" data-mini="true"';
    
    if ($paper['id'] == $clip_files) print ' checked="checked"';
                    
    print '><label for="checkbox-clipboard-' . $paper['id'] . '">Clipboard</label></form>';

    print '</td></tr></table>';
    
    // SECOND ROW - TITLE, JOURNAL, YEAR, AUTHOR

    print PHP_EOL . '<div class="ui-body ui-body-a" style="margin:0.75em 0;font-size:0.9em">' . $paper['title'] . '<br><span style="font-weight:normal">';

    if (!empty($paper['authors']))
        print PHP_EOL . $first_author . $etal . ' ';

    print (!empty($date)) ? ' (' . $date . ')' : '';

    if (!empty($paper['journal'])) {
    
        echo '<br><i>' . $paper['journal'] . '</i>';
        
    } elseif (!empty($paper['secondary_title'])) {
        
        echo '<br><i>' . $paper['secondary_title'] . '</i>';
    }

    

    print '</span></div>';
    
    // THIRD ROW, ABSTRACT, NOTES, SUPPLEMENTARY FILES

    print '<div data-role="collapsible-set" data-theme="a" data-content-theme="a" style="margin:8px 0" class="item-accordeon">
                <div data-role="collapsible">
                    <h3 class="accordeon">Abstract</h3>
                    <div style="font-size:0.9em">' . (empty($paper['abstract']) ? 'No abstract.' : $paper['abstract']) . '</div>
                </div>
                <div data-role="collapsible">
                    <h3 class="accordeon">Notes</h3>
                    <div style="font-size:0.9em">' . (empty($paper['notes']) ? 'No notes.' : $paper['notes']) . '</div>
                </div>
                <div data-role="collapsible">
                    <h3 class="accordeon">Supplementary files</h3>' . $url_filename . '
                </div>
            </div>';
}
?>