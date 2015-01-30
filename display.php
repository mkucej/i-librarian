<?php
include_once 'data.php';
include_once 'functions.php';
session_write_close();

if (!isset($_GET['from'])) {
    $from = '0';
} else {
    settype($_GET['from'], "integer");
    $from = $_GET['from'];
}

// CACHING

if (isset($_GET['from']) && !isset($_GET['browse']['No PDF']) && !isset($_GET['browse']['Not Indexed'])) {
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
    $total_files_array = read_export_files($db_change);
}

if (!isset($_GET['project'])) {
    $project = '';
} else {
    $project = $_GET['project'];
}

if (!isset($_SESSION['limit'])) {
    $limit = 10;
} else {
    settype($_SESSION['limit'], "integer");
    $limit = $_SESSION['limit'];
}

if (!isset($_SESSION['orderby'])) {
    $orderby = 'id';
} else {
    $orderby = $_SESSION['orderby'];
}

if (!isset($_SESSION['display'])) {
    $display = 'summary';
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

    database_connect($database_path, 'library');

    $shelf_files = array();
    $shelf_files = read_shelf($dbHandle);

    $desktop_projects = array();
    $desktop_projects = read_desktop($dbHandle);

    if ($_GET['select'] == 'shelf') {
        $all_in = "INNER JOIN shelves ON library.id=shelves.fileID WHERE shelves.userID=" . intval($_SESSION['user_id']);
    }

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
        $project_files = $dbHandle->query("SELECT fileID FROM projectsfiles WHERE projectID=" . intval($project_id));
        $project_files = $project_files->fetchAll(PDO::FETCH_COLUMN);
        $in = implode(',', $project_files);
        $all_in = "WHERE id IN ($in)";
        $project_files = null;

        if (!empty($desktop_projects)) {
            foreach ($desktop_projects as $desktop_project) {
                if ($desktop_project['projectID'] == $project_id)
                    $display_project = $desktop_project['project'];
            }
        }
    }

    if ($_GET['select'] == 'clipboard' && !empty($_SESSION['session_clipboard'])) {
        $in = join(",", $_SESSION['session_clipboard']);
        $all_in = "WHERE id IN ($in)";
    }

    if ($_GET['select'] == 'clipboard' && empty($_SESSION['session_clipboard'])) {
        $all_in = "WHERE id IN ()";
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
                $category_sql_array[] = "SELECT id FROM library WHERE id NOT IN (SELECT fileID FROM filescategories)";
                $query_translation = '!unassigned';
            } else {
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
            $browse_string_array[] = "(authors LIKE '%$query2%' ESCAPE '\' OR authors_ascii LIKE '%$query2%' ESCAPE '\') AND (regexp_match(authors, '$query2', 0) OR regexp_match(authors_ascii, '$query2', 0))";
        } elseif ($column == 'year') {
            $browse_string_array[] = "(year LIKE '$query2%' ESCAPE '\')";
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

    $ordering = 'ASC';
    if ($orderby == 'year' || $orderby == 'addition_date' || $orderby == 'rating' || $orderby == 'id')
        $ordering = 'DESC';

    $dbHandle->sqliteCreateFunction('regexp_match', 'sqlite_regexp', 3);

    if ($column_string == 'all') {

        if (!isset($total_files_array)) {
            $result = $dbHandle->query("SELECT id FROM library $all_in ORDER BY $orderby COLLATE NOCASE $ordering");
            get_db_error($dbHandle, basename(__FILE__), __LINE__);
            $total_files_array = $result->fetchAll(PDO::FETCH_COLUMN);
            $result = null;
            save_export_files($total_files_array);
        }

        $display_files_array = array_slice($total_files_array, $from, $limit);
        $display_files = join(",", $display_files_array);
        $display_files = "id IN ($display_files)";

        $result = $dbHandle->query("SELECT id,file,authors,title,journal,secondary_title,year,volume,pages,abstract,uid,doi,url,addition_date,rating,bibtex
					FROM library WHERE $display_files");
    } elseif ($column_string == 'miscellaneous') {

        if (array_key_exists('No PDF', $_GET['browse'])) {

            $pdfs = array();

            chdir('library');

            $pdfs = glob('*.pdf', GLOB_NOSORT);
            $pds_string = join("','", (array) $pdfs);
            $pds_string = "file NOT IN ('" . $pds_string . "')";

            chdir('..');

            $result = $dbHandle->query("SELECT id FROM library WHERE $pds_string ORDER BY $orderby COLLATE NOCASE $ordering");

            $total_files_array = $result->fetchAll(PDO::FETCH_COLUMN);
            $result = null;
            save_export_files($total_files_array);

            $display_files_array = array_slice($total_files_array, $from, $limit);
            $display_files = join(",", $display_files_array);
            $display_files = "id IN ($display_files)";

            $result = $dbHandle->query("SELECT id,file,authors,title,journal,secondary_title,year,volume,pages,abstract,uid,doi,url,addition_date,rating,bibtex
						FROM library WHERE $display_files");
        }

        if (array_key_exists('My Items', $_GET['browse'])) {

            $result = $dbHandle->query("SELECT id FROM library WHERE added_by=" . intval($_SESSION['user_id']) . " ORDER BY $orderby COLLATE NOCASE $ordering");

            $total_files_array = $result->fetchAll(PDO::FETCH_COLUMN);
            $result = null;
            save_export_files($total_files_array);

            $display_files_array = array_slice($total_files_array, $from, $limit);
            $display_files = join(",", $display_files_array);
            $display_files = "id IN ($display_files)";

            $result = $dbHandle->query("SELECT id,file,authors,title,journal,secondary_title,year,volume,pages,abstract,uid,doi,url,addition_date,rating,bibtex
						FROM library WHERE $display_files");
        }

        if (array_key_exists("Others' Items", $_GET['browse'])) {

            $result = $dbHandle->query("SELECT id FROM library WHERE added_by!=" . intval($_SESSION['user_id']) . " ORDER BY $orderby COLLATE NOCASE $ordering");

            $total_files_array = $result->fetchAll(PDO::FETCH_COLUMN);
            $result = null;
            save_export_files($total_files_array);

            $display_files_array = array_slice($total_files_array, $from, $limit);
            $display_files = join(",", $display_files_array);
            $display_files = "id IN ($display_files)";

            $result = $dbHandle->query("SELECT id,file,authors,title,journal,secondary_title,year,volume,pages,abstract,uid,doi,url,addition_date,rating,bibtex
						FROM library WHERE $display_files");
        }

        if (array_key_exists('Not in Shelf', $_GET['browse'])) {

            $not_shelf = join(",", $shelf_files);
            $not_shelf = "id NOT IN(" . $not_shelf . ")";

            $result = $dbHandle->query("SELECT id FROM library WHERE $not_shelf ORDER BY $orderby COLLATE NOCASE $ordering");

            $total_files_array = $result->fetchAll(PDO::FETCH_COLUMN);
            $result = null;
            save_export_files($total_files_array);

            $display_files_array = array_slice($total_files_array, $from, $limit);
            $display_files = join(",", $display_files_array);
            $display_files = "id IN ($display_files)";

            $result = $dbHandle->query("SELECT id,file,authors,title,journal,secondary_title,year,volume,pages,abstract,uid,doi,url,addition_date,rating,bibtex
						FROM library WHERE $display_files");
        }

        if (array_key_exists('Not Indexed', $_GET['browse'])) {

            $dbHandle->exec("ATTACH DATABASE '" . $database_path . "fulltext.sq3' AS fulltextdatabase");

            $result = $dbHandle->query("SELECT fileID FROM fulltextdatabase.full_text WHERE full_text!=''");
            $indexed = $result->fetchAll(PDO::FETCH_COLUMN);
            $result = null;
            $dbHandle->exec("DETACH DATABASE fulltextdatabase");

            $not_indexed = join(",", $indexed);
            $not_indexed = "id NOT IN(" . $not_indexed . ")";

            $result = $dbHandle->query("SELECT id FROM library WHERE $not_indexed ORDER BY $orderby COLLATE NOCASE $ordering");

            $total_files_array = $result->fetchAll(PDO::FETCH_COLUMN);
            $result = null;
            save_export_files($total_files_array);

            $display_files_array = array_slice($total_files_array, $from, $limit);
            $display_files = join(",", $display_files_array);
            $display_files = "id IN ($display_files)";

            $result = $dbHandle->query("SELECT id,file,authors,title,journal,secondary_title,year,volume,pages,abstract,uid,doi,url,addition_date,rating,bibtex
						FROM library WHERE $display_files");
        }
    } elseif ($column_string == 'history') {

        $quoted_history = $dbHandle->quote($database_path . 'history.sq3');
        $dbHandle->exec("ATTACH DATABASE $quoted_history AS history");

        $dbHandle->exec("DELETE FROM history.usersfiles WHERE " . time() . "-viewed>28800");

        $result = $dbHandle->query("SELECT id FROM library
                    WHERE id IN (SELECT fileID FROM history.usersfiles WHERE userID=" . intval($_SESSION['user_id']) . ")
                    ORDER BY $orderby COLLATE NOCASE $ordering");

        if (is_object($result)) {

            $total_files_array = $result->fetchAll(PDO::FETCH_COLUMN);
            $result = null;

            $dbHandle->exec("DETACH DATABASE history");

            save_export_files($total_files_array);

            $display_files_array = array_slice($total_files_array, $from, $limit);
            $display_files = join(",", $display_files_array);
            $display_files = "id IN ($display_files)";

            $result = $dbHandle->query("SELECT id,file,authors,title,journal,secondary_title,year,volume,pages,abstract,uid,doi,url,addition_date,rating,bibtex
                                                FROM library WHERE $display_files");
        }
    } else {

        if (!isset($total_files_array)) {
            if (!empty($category_sql)) {
                $result = $dbHandle->query("SELECT id FROM library $all_in $where id IN (" . $category_sql . ") ORDER BY $orderby COLLATE NOCASE $ordering");
            } else {
                $result = $dbHandle->query("SELECT id FROM library $all_in $where $browse_string ORDER BY $orderby COLLATE NOCASE $ordering");
            }

            $total_files_array = $result->fetchAll(PDO::FETCH_COLUMN);
            $result = null;
            save_export_files($total_files_array);
        }

        $display_files_array = array_slice($total_files_array, $from, $limit);
        $display_files = join(",", $display_files_array);
        $display_files = "id IN ($display_files)";

        $result = $dbHandle->query("SELECT id,file,authors,title,journal,secondary_title,year,volume,pages,abstract,uid,doi,url,addition_date,rating,bibtex
                                    FROM library WHERE $display_files");
    }

    $rows = count($total_files_array);
    $total_files_array = null;

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

        //TRUNCATE SHELF FILES ARRAY TO ONLY DISPLAYED FILES IMPROVES PERFROMANCE FOR LARGE SHELVES
        if (count($shelf_files) > 5000)
            $shelf_files = array_intersect((array) $display_files_array, (array) $shelf_files);

        //PRE-FETCH CATEGORIES, PROJECTS, NOTES FOR DISPLAYED ITEMS INTO TEMP DATABASE TO OFFLOAD THE MAIN DATABASE
        $display_files2 = join(",", $display_files_array);
        try {
            $tempdbHandle = new PDO('sqlite::memory:');
        } catch (PDOException $e) {
            print "Error: " . $e->getMessage() . "<br/>";
            die();
        }
        $quoted_path = $tempdbHandle->quote($database_path . 'library.sq3');
        $tempdbHandle->exec("ATTACH DATABASE $quoted_path AS librarydb");

        $tempdbHandle->beginTransaction();

        $tempdbHandle->exec("CREATE TABLE temp_categories (
                    fileID integer NOT NULL,
                    categoryID integer NOT NULL,
                    category text NOT NULL)");
        $tempdbHandle->exec("CREATE TABLE temp_projects (
                    fileID integer NOT NULL,
                    projectID integer NOT NULL)");
        $tempdbHandle->exec("CREATE TABLE temp_notes (
                    fileID integer NOT NULL,
                    notesID integer NOT NULL,
                    notes text NOT NULL)");

        $tempdbHandle->exec("INSERT INTO temp_categories SELECT fileID,filescategories.categoryID,category
                                FROM librarydb.categories INNER JOIN librarydb.filescategories ON filescategories.categoryID=categories.categoryID
                                WHERE fileID IN ($display_files2)");

        $tempdbHandle->exec("INSERT INTO temp_projects SELECT fileID,projectID
                                FROM librarydb.projectsfiles WHERE fileID IN ($display_files2)");

        if (isset($_SESSION['auth']))
            $tempdbHandle->exec("INSERT INTO temp_notes SELECT fileID,notesID,notes
                                FROM librarydb.notes WHERE fileID IN ($display_files2) AND userID=" . intval($_SESSION['user_id']));

        $tempdbHandle->commit();
        $tempdbHandle->exec("DETACH DATABASE librarydb");
        ?>
        <div id="display-content" style="width:100%;height:100%"
             data-redirection="<?php print preg_replace('/(from=\d*)(\&|$)/', '$2', basename($_SERVER['PHP_SELF']) . '?' . $_SERVER['QUERY_STRING']); ?>">
            <div class="alternating_row" style="padding:0.35em;padding-bottom: 0;border-bottom:1px solid #c5c6c8">
                <div id="exportbutton" class="ui-state-highlight ui-corner-all" style="display:inline-block;padding:0.2em 0.4em">
                    &nbsp;<i class="fa fa-briefcase"></i> Export&nbsp;
                </div>
                <div id="omnitoolbutton" class="ui-state-highlight ui-corner-all" style="display:inline-block;margin-left:2px;padding:0.2em 0.4em">
                    &nbsp;<i class="fa fa-wrench"></i> Omnitool&nbsp;</div>
                <?php
                if ($_GET['select'] == 'desk') {
                    print '<div class="ui-state-highlight ui-corner-all" style="display:inline-block;margin-left:2px;padding:0.2em 0.4em">
                <a href="rss.php?project=' . $project . '" target="_blank" style="display:block">&nbsp;<i class="fa fa-rss"></i> Project RSS</a></div>';
                } else {

                    print '<div class="ui-state-highlight ui-corner-all" style="display:inline-block;margin-left:2px;padding:0.2em 0.4em">
                <a href="rss.php" target="_blank" style="display:block">&nbsp;<i class="fa fa-rss"></i> RSS</a></div>';
                }
                ?>
                <div class="ui-state-highlight ui-corner-all" style="display:inline-block;margin-left:2px;padding:0.2em 0.4em" id="printlist">
                    &nbsp;<i class="fa fa-print"></i> Print&nbsp;
                </div>
                <div style="float:right;margin: 2px;margin-top: 0">
                    <span style="position:relative;top:-7px">
                        Display
                    </span>
                    <select id="select-display" style="width:9em">
                        <option value="brief" <?php print $display == 'brief' ? 'selected' : ''; ?>>Title</option>
                        <option value="summary" <?php print $display == 'summary' ? 'selected' : ''; ?>>Summary</option>
                        <option value="abstract" <?php print $display == 'abstract' ? 'selected' : ''; ?>>Abstract</option>
                        <option value="icons" <?php print $display == 'icons' ? 'selected' : ''; ?>>Icons</option>
                    </select>
                    <span style="position:relative;top:-7px">
                        Order
                    </span>
                    <select id="select-order" style="width:11em">
                        <option value="id" <?php print $orderby == 'id' ? 'selected' : ''; ?>>Date Added</option>
                        <option value="year" <?php print $orderby == 'year' ? 'selected' : ''; ?>>Date Published</option>
                        <option value="journal" <?php print $orderby == 'journal' ? 'selected' : ''; ?>>Journal</option>
                        <option value="rating" <?php print $orderby == 'rating' ? 'selected' : ''; ?>>Rating</option>
                        <option value="title" <?php print $orderby == 'title' ? 'selected' : ''; ?>>Title</option>
                    </select>
                    <span style="position:relative;top:-7px">
                        Show
                    </span>
                    <select id="select-number" style="width:6em">
                        <option value="5" <?php print $limit == 5 ? 'selected' : ''; ?>>5</option>
                        <option value="10" <?php print $limit == 10 ? 'selected' : ''; ?>>10</option>
                        <option value="15" <?php print $limit == 15 ? 'selected' : ''; ?>>15</option>
                        <option value="20" <?php print $limit == 20 ? 'selected' : ''; ?>>20</option>
                        <option value="50" <?php print $limit == 50 ? 'selected' : ''; ?>>50</option>
                        <option value="100" <?php print $limit == 100 ? 'selected' : ''; ?>>100</option>
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

            print '<div class="ui-state-highlight ui-corner-top' . ($from == 0 ? ' ui-state-disabled' : '') . '" style="float:left;margin-left:2px;width:26px">'
                    . ($from == 0 ? '' : '<a href="' . htmlspecialchars("display.php?select=$_GET[select]&project=$project&from=0&$browse_url_string") . '" class="navigation" style="display:block;width:26px">') .
                    '&nbsp;<i class="fa fa-caret-left"></i> <i class="fa fa-caret-left"></i>&nbsp;'
                    . ($from == 0 ? '' : '</a>') .
                    '</div>';

            print '<div class="ui-state-highlight ui-corner-top' . ($from == 0 ? ' ui-state-disabled' : '') . '" style="float:left;margin-left:2px;width:4em">'
                    . ($from == 0 ? '' : '<a title="Shortcut: A" class="navigation prevpage" href="' . htmlspecialchars("display.php?select=$_GET[select]&project=$project&from=" . ($from - $limit) . "&$browse_url_string") . '" style="color:black;display:block;width:100%">') .
                    '<i class="fa fa-caret-left"></i>&nbsp;Back'
                    . ($from == 0 ? '' : '</a>') .
                    '</div>';

            print '</td><td style="text-align: center">Items ' . $items_from . '-' . $items_to . ' of <span id="total-items">' . $rows . '</span></td><td style="width:19em">';

            (($rows % $limit) == 0) ? $lastpage = $rows - $limit : $lastpage = $rows - ($rows % $limit);

            print '<div class="ui-state-highlight ui-corner-top' . (($rows > ($from + $limit)) ? '' : ' ui-state-disabled') . '" style="float:right;margin-right:2px;width:26px">'
                    . (($rows > ($from + $limit)) ? '<a class="navigation" href="' . htmlspecialchars("display.php?select=$_GET[select]&project=$project&from=$lastpage&$browse_url_string") . '" style="display:block;width:26px">' : '') .
                    '<i class="fa fa-caret-right"></i>&nbsp;<i class="fa fa-caret-right"></i>'
                    . (($rows > ($from + $limit)) ? '</a>' : '') .
                    '</div>';

            print '<div class="ui-state-highlight ui-corner-top' . (($rows > ($from + $limit)) ? '' : ' ui-state-disabled') . '" style="float:right;margin-right:2px;width: 4em">'
                    . (($rows > ($from + $limit)) ? '<a title="Shortcut: D" class="navigation nextpage" href="' . htmlspecialchars("display.php?select=$_GET[select]&project=$project&from=" . ($from + $limit) . "&$browse_url_string") . '" style="color:black;display:block;width:100%">' : '') .
                    '&nbsp;Next <i class="fa fa-caret-right"></i>&nbsp;'
                    . (($rows > ($from + $limit)) ? '</a>' : '') .
                    '</div>';

            print '<div class="ui-state-highlight ui-corner-top pgdown" style="float: right;width: 4em;margin-right:2px">PgDn</div>';

            print '</td></tr></table>';

            show_search_results($result, $_GET['select'], $display, $shelf_files, $desktop_projects, $tempdbHandle);

            print '<table cellspacing="0" class="top" style="margin:1px 0px 2px 0px"><tr><td style="width: 50%">';

            print '<div class="ui-state-highlight ui-corner-bottom' . ($from == 0 ? ' ui-state-disabled' : '') . '" style="float:left;margin-left:2px;width:26px">'
                    . ($from == 0 ? '' : '<a href="' . htmlspecialchars("display.php?select=$_GET[select]&project=$project&from=0&$browse_url_string") . '" class="navigation" style="display:block;width:26px">') .
                    '<i class="fa fa-caret-left"></i> <i class="fa fa-caret-left"></i>'
                    . ($from == 0 ? '' : '</a>') .
                    '</div>';

            print '<div class="ui-state-highlight ui-corner-bottom' . ($from == 0 ? ' ui-state-disabled' : '') . '" style="float:left;margin-left:2px;width:4em">'
                    . ($from == 0 ? '' : '<a title="Shortcut: A" class="navigation" href="' . htmlspecialchars("display.php?select=$_GET[select]&project=$project&from=" . ($from - $limit) . "&$browse_url_string") . '" style="color:black;display:block;width:100%">') .
                    '<i class="fa fa-caret-left"></i> Back'
                    . ($from == 0 ? '' : '</a>') .
                    '</div>';

            print '</td><td style="width:50%">';

            print '<div class="ui-state-highlight ui-corner-bottom' . (($rows > ($from + $limit)) ? '' : ' ui-state-disabled') . '" style="float:right;margin-right:2px;width:26px">'
                    . (($rows > ($from + $limit)) ? '<a class="navigation" href="' . htmlspecialchars("display.php?select=$_GET[select]&project=$project&from=$lastpage&$browse_url_string") . '" style="display:block;width:26px">' : '') .
                    '<i class="fa fa-caret-right"></i> <i class="fa fa-caret-right"></i>'
                    . (($rows > ($from + $limit)) ? '</a>' : '') .
                    '</div>';

            print '<div class="ui-state-highlight ui-corner-bottom' . (($rows > ($from + $limit)) ? '' : ' ui-state-disabled') . '" style="float:right;margin-right:2px;width:4em">'
                    . (($rows > ($from + $limit)) ? '<a title="Shortcut: D" class="navigation" href="' . htmlspecialchars("display.php?select=$_GET[select]&project=$project&from=" . ($from + $limit) . "&$browse_url_string") . '" style="color:black;display:block;width:100%">' : '') .
                    'Next <i class="fa fa-caret-right"></i>'
                    . (($rows > ($from + $limit)) ? '</a>' : '') .
                    '</div>';

            print '<div class="ui-state-highlight ui-corner-bottom pgup" style="float:right;width:4em;margin-right:2px">PgUp</div>';

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