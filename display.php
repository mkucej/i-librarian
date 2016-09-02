<?php
include_once 'data.php';
include_once 'functions.php';

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

if (!isset($_SESSION['limit'])) {
    $limit = $_SESSION['limit'] = 10;
} else {
    settype($_SESSION['limit'], "integer");
    $limit = $_SESSION['limit'];
}

if (!isset($_SESSION['orderby'])) {
    $orderby = $_SESSION['orderby'] = 'id';
} else {
    $orderby = $_SESSION['orderby'];
}

if (!isset($_SESSION['display'])) {
    $display = $_SESSION['display'] = 'summary';
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

// CACHING to FILE

if (isset($_GET['from']) && !isset($_GET['browse']['No PDF']) && !isset($_GET['browse']['Discussed Items'])) {
    $cache_name = cache_name();
    $db_change = database_change(array(
        'library',
        'shelves',
        'projects',
        'projectsusers',
        'projectsfiles',
        'filescategories',
        'notes'
            ), array('full_text'), array('clipboard'));
    cache_start($db_change);
}

if (isset($_GET['browse'])) {

    $in = '';
    $all_in = '';

    database_connect(IL_DATABASE_PATH, 'library');

    // Shelf, project, clipboard constraints.
    if ($_GET['select'] == 'shelf') {
        $all_in = "WHERE id IN (SELECT fileID FROM shelves WHERE fileID>0 AND userID=" . intval($_SESSION['user_id']) . ")";
//        $all_in = "INNER JOIN shelves ON library.id=shelves.fileID WHERE shelves.userID=" . intval($_SESSION['user_id']);
    }

    // Get user's dektop projects.
    $desktop_projects = array();
    $desktop_projects = read_desktop($dbHandle);

    if ($_GET['select'] == 'desk') {
        $project_id = '';
        $display_project = '';
        $all_in = "WHERE id IN ()";
        if (!empty($desktop_projects)) {
            $project_id = $desktop_projects[0]['projectID'];
            $display_project = $desktop_projects[0]['project'];
        }

        if (isset($_GET['project']))
            $project_id = $_GET['project'];
        $all_in = "WHERE id IN (SELECT fileID FROM projectsfiles WHERE projectID=" . intval($project_id) . ")";
//        $all_in = "INNER JOIN projectsfiles ON library.id=projectsfiles.fileID WHERE projectID=" . intval($project_id);
        $project_files = null;

        if (!empty($desktop_projects)) {
            foreach ($desktop_projects as $desktop_project) {
                if ($desktop_project['projectID'] == $project_id)
                    $display_project = $desktop_project['project'];
            }
        }
    }

    if ($_GET['select'] == 'clipboard') {

        $all_in = "WHERE id IN (SELECT id FROM clipboard.files)";
    }

    empty($all_in) ? $where = 'WHERE' : $where = 'AND';

    $category_sql_array = array();

    while (list($query, $column) = each($_GET['browse'])) {

        $query2 = str_replace("\\", "\\\\", $query);
        $query2 = str_replace('%', '\%', $query2);
        $query2 = str_replace('_', '\_', $query2);
        $query2 = str_replace("'", "''", $query2);

        // check column name for malarkey
        if (preg_match('/[^a-z_0-9]/', $column) !== 0)
            die('Invalid query.');

        if ($column == 'category') {
            $query = intval($query);
            if ($query == 0) {
                $category_id = 0;
                $category_sql_array[] = "SELECT fileID FROM filescategories";
                $query_translation = '!unassigned';
            } else {
                $category_id = $query;
                $category_sql_array[] = "SELECT fileID FROM filescategories WHERE categoryID=$query";
                $result = $dbHandle->query("SELECT category FROM categories WHERE categoryID=$query LIMIT 1");
                $query_translation = $result->fetchColumn();
                $result = null;
            }
            $category_sql = implode(" INTERSECT ", $category_sql_array);
        } elseif ($column == 'keywords') {
            $browse_string_array[] = "(keywords LIKE '$query2' ESCAPE '\' OR keywords LIKE '%/ $query2' ESCAPE '\' OR keywords LIKE '%/ $query2 /%' ESCAPE '\' OR keywords LIKE '$query2 /%' ESCAPE '\'
						 OR keywords LIKE '%/$query2' ESCAPE '\' OR keywords LIKE '%/$query2/%' ESCAPE '\' OR keywords LIKE '$query2/%' ESCAPE '\')";
        } elseif ($column == 'authors') {
            $query2_array = explode(',', $query2);
            $query2 = 'L:"' . trim($query2_array[0]) . '",F:"' . trim($query2_array[1]) . '"';
            $browse_string_array[] = "authors LIKE '%$query2%' ESCAPE '\' OR authors_ascii LIKE '%$query2%' ESCAPE '\'";
        } elseif ($column == 'year') {
            $browse_string_array[] = "year LIKE '$query2%' ESCAPE '\'";
        } else {
            $browse_string_array[] = "$column='$query2'";
        }

        if ($column != 'all')
            $query_array[] = "$column: " . (empty($query_translation) ? $query : $query_translation);

        $column_string = $column;

        $browse_url_array[] = "browse[" . urlencode($query) . "]=" . urlencode($column);
    }

    $browse_url_string = join("&", $browse_url_array);

    if (isset($browse_string_array) && is_array($browse_string_array))
        $browse_string = join(' AND ', $browse_string_array);
    if (isset($query_array) && is_array($query_array))
        $query_display_string = join(' AND ', $query_array);

    $ordering = 'ORDER BY ' . $orderby . ' COLLATE NOCASE ASC';
    if ($orderby == 'year' || $orderby == 'addition_date' || $orderby == 'rating' || $orderby == 'id')
        $ordering = 'ORDER BY ' . $orderby . ' DESC';

    $dbHandle->sqliteCreateFunction('regexp_match', 'sqlite_regexp', 3);

    if ($column_string == 'all') {

            if ($_GET['select'] == 'library') {
                perform_search("SELECT id FROM library $ordering");
            } else {
                perform_search("SELECT id FROM library $all_in $ordering");
            }

    } elseif ($column_string == 'miscellaneous') {

        if (array_key_exists('No PDF', $_GET['browse'])) {

            $glob = new GlobIterator(IL_PDF_PATH . DIRECTORY_SEPARATOR . '[0-9]' . DIRECTORY_SEPARATOR . '[0-9]' . DIRECTORY_SEPARATOR . '*.pdf');
            foreach ($glob as $pdf) {
                $pds_arr[] = intval(basename($pdf->getFilename(), '.pdf'));
            }
            $pds_string = join(",", (array) $pds_arr);
            $pds_string = "id NOT IN (" . $pds_string . ")";

            perform_search("SELECT id FROM library WHERE $pds_string $ordering");
        }

        if (array_key_exists('My Items', $_GET['browse'])) {

            perform_search("SELECT id FROM library WHERE added_by=" . intval($_SESSION['user_id']) . " $ordering");
        }

        if (array_key_exists("Others' Items", $_GET['browse'])) {

            perform_search("SELECT id FROM library WHERE id NOT IN (SELECT id FROM library WHERE added_by="
                    . intval($_SESSION['user_id']) . ") $ordering");
        }

        if (array_key_exists('Not in Shelf', $_GET['browse'])) {

            perform_search("SELECT id FROM library WHERE id NOT IN (SELECT fileID from shelves WHERE userID="
                    . intval($_SESSION['user_id']) . ") $ordering");
        }

        if (array_key_exists('Not Indexed', $_GET['browse'])) {

            perform_search("SELECT id FROM library WHERE id NOT IN (SELECT fileID FROM fulltextdatabase.full_text) $ordering");
        }

        if (array_key_exists('Items with Notes', $_GET['browse'])) {

            perform_search("SELECT id FROM library WHERE id IN (SELECT fileID FROM notes WHERE userID="
                    . intval($_SESSION['user_id']) . ") $ordering");
        }

        if (array_key_exists('Discussed Items', $_GET['browse'])) {

            perform_search("SELECT id FROM library WHERE id IN (SELECT fileID FROM discussionsdatabase.filediscussion) $ordering");

        }
    } elseif ($column_string == 'history') {

        $quoted_history = $dbHandle->quote(IL_DATABASE_PATH . DIRECTORY_SEPARATOR . 'history.sq3');
        $dbHandle->exec("ATTACH DATABASE $quoted_history AS history");
        $dbHandle->exec("DELETE FROM history.usersfiles WHERE " . time() . "-viewed>28800");
        $dbHandle->exec("DETACH DATABASE history");

        perform_search("SELECT id FROM library WHERE id IN (SELECT fileID FROM history.usersfiles WHERE userID=" . intval($_SESSION['user_id']) . ") $ordering");

    } else {

        if (!empty($category_sql)) {

            if ($category_id === 0) {
                    perform_search("SELECT id FROM library $all_in $where id NOT IN (" . $category_sql . ") $ordering");
            } else {
                    perform_search("SELECT id FROM library $all_in $where id IN (" . $category_sql . ") $ordering");
            }
        } else {

            perform_search("SELECT id FROM library $all_in $where $browse_string $ordering");
        }
    }

//    $dbHandle = null;

    if ($rows > 0) {

        $result = $result->fetchAll(PDO::FETCH_ASSOC);

        foreach ($result as $item) {

            $display_files_array[] = $item['id'];
        }

        $display_files2 = join(",", $display_files_array);

        // Read shelf files.
        $shelf_files = read_shelf($dbHandle, $display_files_array);

        // Read clipboard files.
        attach_clipboard($dbHandle);
        $clip_result = $dbHandle->query("SELECT id FROM clipboard.files WHERE id IN ($display_files2)");
        $clip_files = $clip_result->fetchAll(PDO::FETCH_COLUMN);
        $clip_result = null;

        //PRE-FETCH CATEGORIES, PROJECTS FOR DISPLAYED ITEMS INTO TEMP DATABASE TO OFFLOAD THE MAIN DATABASE
        try {
            $tempdbHandle = new PDO('sqlite::memory:');
        } catch (PDOException $e) {
            print "Error: " . $e->getMessage() . "<br/>";
            die();
        }
        $quoted_path = $tempdbHandle->quote(IL_DATABASE_PATH . DIRECTORY_SEPARATOR . 'library.sq3');
        $tempdbHandle->exec("ATTACH DATABASE $quoted_path AS librarydb");

        $tempdbHandle->beginTransaction();

        $tempdbHandle->exec("CREATE TABLE temp_categories (
                    fileID integer NOT NULL,
                    categoryID integer NOT NULL,
                    category text NOT NULL)");
        $tempdbHandle->exec("CREATE TABLE temp_projects (
                    fileID integer NOT NULL,
                    projectID integer NOT NULL)");

        $tempdbHandle->exec("INSERT INTO temp_categories SELECT fileID,filescategories.categoryID,category
                                FROM librarydb.categories INNER JOIN librarydb.filescategories ON filescategories.categoryID=categories.categoryID
                                WHERE fileID IN ($display_files2)");

        $tempdbHandle->exec("INSERT INTO temp_projects SELECT fileID,projectID
                                FROM librarydb.projectsfiles WHERE fileID IN ($display_files2)");

        $tempdbHandle->commit();
        $tempdbHandle->exec("DETACH DATABASE librarydb");

        ?>
        <div id="display-content" style="width:100%;height:100%">
            <div class="alternating_row" style="padding: 4px 6px 2px 6px;border-bottom:1px solid rgba(0,0,0,0.15)">
                <button id="exportbutton" style="display:inline">
                    <i class="fa fa-briefcase"></i> Export
                </button>
                <button id="omnitoolbutton" class="ui-state-default ui-corner-all">
                    <i class="fa fa-wrench"></i> Omnitool
                </button>
                <?php
                if ($_GET['select'] == 'desk') {
                    print '<a href="rss.php?project=' . $project . '" target="_blank" id="rss-link">&nbsp;<i class="fa fa-rss"></i> Project RSS</a>';
                } else {

                    print '<a href="rss.php" target="_blank" id="rss-link">&nbsp;<i class="fa fa-rss"></i> RSS</a>';
                }
                ?>
                <button class="ui-state-default ui-corner-all" id="printlist">
                    <i class="fa fa-print"></i> Print
                </button>
                <div style="float:right;margin-top: 2px;">
                    <select id="select-display" style="width:8.5em">
                        <optgroup label="Display">
                            <option value="brief" <?php print $display == 'brief' ? 'selected' : ''; ?>>Title</option>
                            <option value="summary" <?php print $display == 'summary' ? 'selected' : ''; ?>>Summary</option>
                            <option value="abstract" <?php print $display == 'abstract' ? 'selected' : ''; ?>>Abstract</option>
                            <option value="icons" <?php print $display == 'icons' ? 'selected' : ''; ?>>Icons</option>
                        </optgroup>
                    </select>
                    <select id="select-order" style="width:11em">
                        <optgroup label="Order by">
                            <option value="id" <?php print $orderby == 'id' ? 'selected' : ''; ?>>Date Added</option>
                            <option value="year" <?php print $orderby == 'year' ? 'selected' : ''; ?>>Date Published</option>
                            <option value="journal" <?php print $orderby == 'journal' ? 'selected' : ''; ?>>Journal</option>
                            <option value="rating" <?php print $orderby == 'rating' ? 'selected' : ''; ?>>Rating</option>
                            <option value="title" <?php print $orderby == 'title' ? 'selected' : ''; ?>>Title</option>
                        </optgroup>
                    </select>
                    <select id="select-number" style="width:5.5em">
                        <optgroup label="Show">
                            <option value="5" <?php print $limit == 5 ? 'selected' : ''; ?>>5</option>
                            <option value="10" <?php print $limit == 10 ? 'selected' : ''; ?>>10</option>
                            <option value="15" <?php print $limit == 15 ? 'selected' : ''; ?>>15</option>
                            <option value="20" <?php print $limit == 20 ? 'selected' : ''; ?>>20</option>
                            <option value="50" <?php print $limit == 50 ? 'selected' : ''; ?>>50</option>
                            <option value="100" <?php print $limit == 100 ? 'selected' : ''; ?>>100</option>
                        </optgroup>
                    </select>
                </div>
                <div style="clear:both"></div>
            </div>
            <?php
        }
        if (isset($_GET['select']) && $_GET['select'] == 'shelf' && isset($_SESSION["auth"])) {
            $what = "Shelf";
        } elseif (isset($_GET['select']) && $_GET['select'] == 'clipboard') {
            $what = "Clipboard";
        } elseif (isset($_GET['select']) && $_GET['select'] == 'desk') {
            $what = htmlspecialchars("Project: $display_project");
        } else {
            $what = "Library";
        }

        print '<div id="list-title" style="font-weight: bold; padding: 6px;text-align:center">' . $what;

        if (!empty($query_display_string))
            print ' &raquo; ' . htmlspecialchars($query_display_string);

        print '</div>';

        if ($rows > 0) {

            $items_from = $from + 1;
            (($from + $limit) > $rows) ? $items_to = $rows : $items_to = $from + $limit;

            print '<table cellspacing="0" class="top" style="margin-bottom:1px"><tr><td style="width: 18em">';

            print '<div class="ui-state-default ui-corner-top' . ($from == 0 ? ' ui-state-disabled' : '') . '" style="float:left;margin-left:2px;width:26px;text-align:center">'
                    . ($from == 0 ? '' : '<a href="' . htmlspecialchars("display.php?select=$_GET[select]&project=$project&from=0&$browse_url_string") . '" class="navigation" style="display:block;width:26px">') .
                    '&nbsp;<i class="fa fa-caret-left"></i> <i class="fa fa-caret-left"></i>&nbsp;'
                    . ($from == 0 ? '' : '</a>') .
                    '</div>';

            print '<div class="ui-state-default ui-corner-top' . ($from == 0 ? ' ui-state-disabled' : '') . '" style="float:left;margin-left:2px;width:4em;text-align:center">'
                    . ($from == 0 ? '' : '<a title="Shortcut: A" class="navigation prevpage" href="' . htmlspecialchars("display.php?select=$_GET[select]&project=$project&from=" . ($from - $limit) . "&$browse_url_string") . '" style="color:black;display:block;width:100%">') .
                    '<i class="fa fa-caret-left"></i>&nbsp;Back'
                    . ($from == 0 ? '' : '</a>') .
                    '</div>';

            print '</td><td style="text-align: center">Items ' . $items_from . '-' . $items_to . ' of <span id="total-items">' . $rows . '</span></td><td style="width:19em">';

            (($rows % $limit) == 0) ? $lastpage = $rows - $limit : $lastpage = $rows - ($rows % $limit);

            print '<div class="ui-state-default ui-corner-top' . (($rows > ($from + $limit)) ? '' : ' ui-state-disabled') . '" style="float:right;margin-right:2px;width:26px;text-align:center">'
                    . (($rows > ($from + $limit)) ? '<a class="navigation" href="' . htmlspecialchars("display.php?select=$_GET[select]&project=$project&from=$lastpage&$browse_url_string") . '" style="display:block;width:26px">' : '') .
                    '<i class="fa fa-caret-right"></i>&nbsp;<i class="fa fa-caret-right"></i>'
                    . (($rows > ($from + $limit)) ? '</a>' : '') .
                    '</div>';

            print '<div class="ui-state-default ui-corner-top' . (($rows > ($from + $limit)) ? '' : ' ui-state-disabled') . '" style="float:right;margin-right:2px;width: 4em;text-align:center">'
                    . (($rows > ($from + $limit)) ? '<a title="Shortcut: D" class="navigation nextpage" href="' . htmlspecialchars("display.php?select=$_GET[select]&project=$project&from=" . ($from + $limit) . "&$browse_url_string") . '" style="color:black;display:block;width:100%">' : '') .
                    '&nbsp;Next <i class="fa fa-caret-right"></i>&nbsp;'
                    . (($rows > ($from + $limit)) ? '</a>' : '') .
                    '</div>';

            print '<div class="ui-state-default ui-corner-top pgdown" style="float: right;width: 4em;margin-right:2px;text-align:center">PgDn</div>';

            print '</td></tr></table>';

            show_search_results($result, $_GET['select'], $shelf_files, $desktop_projects, $clip_files, $tempdbHandle);

            print '<table cellspacing="0" class="top" style="margin:1px 0px 2px 0px"><tr><td style="width: 50%">';

            print '<div class="ui-state-default ui-corner-bottom' . ($from == 0 ? ' ui-state-disabled' : '') . '" style="float:left;margin-left:2px;width:26px;text-align:center">'
                    . ($from == 0 ? '' : '<a href="' . htmlspecialchars("display.php?select=$_GET[select]&project=$project&from=0&$browse_url_string") . '" class="navigation" style="display:block;width:26px">') .
                    '<i class="fa fa-caret-left"></i> <i class="fa fa-caret-left"></i>'
                    . ($from == 0 ? '' : '</a>') .
                    '</div>';

            print '<div class="ui-state-default ui-corner-bottom' . ($from == 0 ? ' ui-state-disabled' : '') . '" style="float:left;margin-left:2px;width:4em;text-align:center">'
                    . ($from == 0 ? '' : '<a title="Shortcut: A" class="navigation" href="' . htmlspecialchars("display.php?select=$_GET[select]&project=$project&from=" . ($from - $limit) . "&$browse_url_string") . '" style="color:black;display:block;width:100%">') .
                    '<i class="fa fa-caret-left"></i> Back'
                    . ($from == 0 ? '' : '</a>') .
                    '</div>';

            print '</td><td style="width:50%">';

            print '<div class="ui-state-default ui-corner-bottom' . (($rows > ($from + $limit)) ? '' : ' ui-state-disabled') . '" style="float:right;margin-right:2px;width:26px;text-align:center">'
                    . (($rows > ($from + $limit)) ? '<a class="navigation" href="' . htmlspecialchars("display.php?select=$_GET[select]&project=$project&from=$lastpage&$browse_url_string") . '" style="display:block;width:26px">' : '') .
                    '<i class="fa fa-caret-right"></i> <i class="fa fa-caret-right"></i>'
                    . (($rows > ($from + $limit)) ? '</a>' : '') .
                    '</div>';

            print '<div class="ui-state-default ui-corner-bottom' . (($rows > ($from + $limit)) ? '' : ' ui-state-disabled') . '" style="float:right;margin-right:2px;width:4em;text-align:center">'
                    . (($rows > ($from + $limit)) ? '<a title="Shortcut: D" class="navigation" href="' . htmlspecialchars("display.php?select=$_GET[select]&project=$project&from=" . ($from + $limit) . "&$browse_url_string") . '" style="color:black;display:block;width:100%">' : '') .
                    'Next <i class="fa fa-caret-right"></i>'
                    . (($rows > ($from + $limit)) ? '</a>' : '') .
                    '</div>';

            print '<div class="ui-state-default ui-corner-bottom pgup" style="float:right;width:4em;margin-right:2px;text-align:center">PgUp</div>';

            print '</td></tr></table><br>';
        } else {
            print '<div style="position:relative;top:43%;left:0;color:#bfbeb9;font-size:28px;width:100%;text-align:center"><b>No Items</b></div>';
        }
    }
    ?>
</div>
<?php
if (isset($_GET['from']) && !isset($_GET['browse']['No PDF']) && !isset($_GET['browse']['Not Indexed'])) {
    cache_store();
}
?>