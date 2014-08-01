<?php
include_once 'data.php';
include_once '../functions.php';
session_write_close();

if (!isset($_GET['from'])) {
    $from = '0';
} else {
    settype($_GET['from'], "integer");
    $from = $_GET['from'];
}

// CACHING

if (isset($_GET['from']) && $_GET['from'] > 0 && !isset($_GET['browse']['No PDF']) && !isset($_GET['browse']['Not Indexed'])) {
    $cache_name = cache_name();
    $db_change = database_change(array(
        'library',
        'shelves',
        'projects',
        'projectsusers',
        'projectsfiles',
        'filescategories',
        'notes'
    ));
    cache_start($db_change);
    $export_files = read_export_files($db_change);
}

if (!isset($_GET['project'])) {
    $project = '';
} else {
    $project = $_GET['project'];
}

$limit = 10;

if (!isset($_SESSION['orderby'])) {
    $orderby = 'id';
} else {
    $orderby = $_SESSION['orderby'];
}

if (!isset($_SESSION['display'])) {
    $display = 'brief';
} else {
    $display = $_SESSION['display'];
}

if ($_GET['select'] != 'library' &&
        $_GET['select'] != 'shelf' &&
        $_GET['select'] != 'desk' &&
        $_GET['select'] != 'clipboard') {

    $_GET['select'] = 'library';
}

if (isset($_GET['browse'])) {

    $in = '';
    $all_in = '';
    $total_files_array = array();

    database_connect($database_path, 'library');

    if ($_GET['select'] == 'shelf') {
        $all_in = "INNER JOIN shelves ON library.id=shelves.fileID WHERE shelves.userID=" . intval($_SESSION['user_id']);
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

    if ($_GET['select'] == 'clipboard' && !empty($_SESSION['session_clipboard'])) {
        $in = join(",", $_SESSION['session_clipboard']);
        $all_in = "WHERE id IN ($in)";
    }

    if ($_GET['select'] == 'clipboard' && empty($_SESSION['session_clipboard'])) {
        $all_in = "WHERE id IN ()";
    }

    $ordering = 'ASC';
    if ($orderby == 'year' || $orderby == 'addition_date' || $orderby == 'rating' || $orderby == 'id')
        $ordering = 'DESC';

    if (isset($export_files)) {
        
        $total_files_array = $export_files;
        
    } else {
        
        $result = $dbHandle->query("SELECT id FROM library $all_in ORDER BY $orderby COLLATE NOCASE $ordering");
        $total_files_array = $result->fetchAll(PDO::FETCH_COLUMN);
        $result = null;
        save_export_files($total_files_array);
    }

    $display_files_array = array_slice($total_files_array, $from, $limit);
    $display_files = join(",", $display_files_array);
    $display_files = "id IN ($display_files)";

    $result = $dbHandle->query("SELECT id,file,authors,title,year FROM library WHERE $display_files");

    $rows = count($total_files_array);

    if ($rows > 0) {

        $result = $result->fetchAll(PDO::FETCH_ASSOC);
        $dbHandle = null;

        //SORT QUERY RESULTS
        $tempresult = array();
        foreach ($result as $row) {
            $key = array_search($row['id'], $display_files_array);
            $tempresult[$key] = $row;
        }
        ksort($tempresult);
        $result = $tempresult;
        ?>
        <div id="display-content" style="width:100%;height:100%">
            <?php
        }

        if ($rows > 0) {

            $items_from = $from + 1;
            (($from + $limit) > $rows) ? $items_to = $rows : $items_to = $from + $limit;

            print '<div style="padding-top:7px;text-align:center;font-size:0.85em">Items '.$items_from.'-'.$items_to.' of <span id="total-items">'.$rows.'</span></div>';

            (($rows % $limit) == 0) ? $lastpage = $rows - $limit : $lastpage = $rows - ($rows % $limit);

            mobile_show_search_results($result, $display);
        } else {
            print '<div style="padding-top:100px;color:#bfbeb9;font-size:28px;text-align:center"><b>No Items</b></div>';
        }
    }
    ?>
</div>
<?php
if (isset($_GET['from']) && $_GET['from'] > 0)
    cache_store();
?>