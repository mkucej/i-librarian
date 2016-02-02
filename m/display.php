<?php
include_once '../data.php';
include_once '../functions.php';

if (!isset($_GET['from'])) {
    $from = '0';
} else {
    settype($_GET['from'], "integer");
    $from = $_GET['from'];
}

if (!isset($_GET['project'])) {
    $project = '';
} else {
    $project = $_GET['project'];
}

$limit = $_SESSION['limit'] = 10;


if (!isset($_SESSION['orderby'])) {
    $orderby = $_SESSION['orderby'] = 'id';
} else {
    $orderby = $_SESSION['orderby'];
}

if (!isset($_SESSION['display'])) {
    $display = $_SESSION['display'] = 'titles';
} else {
    $display = $_SESSION['display'];
}

if ($_GET['select'] != 'library' &&
        $_GET['select'] != 'shelf' &&
        $_GET['select'] != 'desk' &&
        $_GET['select'] != 'clipboard') {

    $_GET['select'] = 'library';
}

// Store current table name hash in session.

$table_name_hash = '';
$table_name_array = $_GET;
$table_name_array['orderby'] = $orderby;
unset($table_name_array['_']);
unset($table_name_array['from']);
unset($table_name_array['limit']);
unset($table_name_array['display']);
$table_name_array['user_id'] = $_SESSION['user_id'];
$table_name_array = array_filter($table_name_array);
ksort($table_name_array);
$table_name_hash = 'search_' . hash('crc32', json_encode($table_name_array));
$_SESSION['display_files'] = $table_name_hash;

session_write_close();

// CACHING

if (isset($_GET['from'])) {
    $cache_name = cache_name();
    $db_change = database_change(array(
        'library',
        'shelves',
        'projects',
        'projectsusers',
        'projectsfiles',
        'filescategories',
        'notes'
            ), array(),array('clipboard'));
    cache_start($db_change);
}

if (isset($_GET['browse'])) {

    echo '<div id="display-content" class="ui-content">';

    $in = '';
    $all_in = '';
    $total_files_array = array();

    database_connect(IL_DATABASE_PATH, 'library');

    if ($_GET['select'] == 'shelf') {
        $all_in = "WHERE id IN (SELECT fileID FROM shelves WHERE fileID>0 AND userID=" . intval($_SESSION['user_id']) . ")";
    }

    if ($_GET['select'] == 'desk') {

        if (isset($_GET['project']))
            $project_id = $_GET['project'];
        $project_files = $dbHandle->query("SELECT fileID FROM projectsfiles WHERE projectID=" . intval($project_id));
        $project_files = $project_files->fetchAll(PDO::FETCH_COLUMN);
        $in = implode(',', $project_files);
        $all_in = "WHERE id IN ($in)";
        $project_files = null;
    }

    if ($_GET['select'] == 'clipboard') {
        $all_in = "WHERE id IN (SELECT id FROM clipboard.files)";
    }

    $ordering = 'ORDER BY ' . $orderby . ' COLLATE NOCASE ASC';
    if ($orderby == 'year' || $orderby == 'addition_date' || $orderby == 'rating' || $orderby == 'id')
        $ordering = 'ORDER BY ' . $orderby . ' DESC';

    perform_search("SELECT id FROM library $all_in $ordering");
        
    if ($rows > 0) {

        $result = $result->fetchAll(PDO::FETCH_ASSOC);

        foreach ($result as $item) {

            $display_files_array[] = $item['id'];
        }

        $display_files2 = join(",", $display_files_array);

        // Read clipboard files.
        attach_clipboard($dbHandle);
        $clip_result = $dbHandle->query("SELECT id FROM clipboard.files WHERE id IN ($display_files2)");
        $clip_files = $clip_result->fetchAll(PDO::FETCH_COLUMN);
        $clip_result = null;

            $items_from = $from + 1;
            (($from + $limit) > $rows) ? $items_to = $rows : $items_to = $from + $limit;

        print '<div style="text-align:center;font-size:0.85em">Items ' . $items_from . '-' . $items_to . ' of <span id="total-items">' . $rows . '</span></div>';

            (($rows % $limit) == 0) ? $lastpage = $rows - $limit : $lastpage = $rows - ($rows % $limit);

        mobile_show_search_results($result, $clip_files);
        } else {
            print '<div style="padding-top:100px;color:#bfbeb9;font-size:28px;text-align:center"><b>No Items</b></div>';
        }
    
    echo '</div>';
    }

if (isset($_GET['from']) && $_GET['from'] > 0)
    cache_store();
?>