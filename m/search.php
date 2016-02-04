<?php
$microtime1 = microtime(true);

include_once '../data.php';
include_once '../functions.php';

if (isset($_GET['newsearch'])) {
    unset($_SESSION['session_anywhere']);
    unset($_SESSION['session_anywhere_separator']);
    unset($_SESSION['session_authors']);
    unset($_SESSION['session_authors_separator']);
    unset($_SESSION['session_journal']);
    unset($_SESSION['session_journal_separator']);
    unset($_SESSION['session_title']);
    unset($_SESSION['session_title_separator']);
    unset($_SESSION['session_abstract']);
    unset($_SESSION['session_abstract_separator']);
    unset($_SESSION['session_keywords']);
    unset($_SESSION['session_keywords_separator']);
    unset($_SESSION['session_secondary_title']);
    unset($_SESSION['session_secondary_title_separator']);
    unset($_SESSION['session_affiliation']);
    unset($_SESSION['session_affiliation_separator']);
    unset($_SESSION['session_search_id']);
    unset($_SESSION['session_year']);
    unset($_SESSION['session_fulltext']);
    unset($_SESSION['session_fulltext_separator']);
    unset($_SESSION['session_notes']);
    unset($_SESSION['session_notes_separator']);
    unset($_SESSION['session_category']);
    unset($_SESSION['session_whole_words']);
    unset($_SESSION['session_case']);
    unset($_SESSION['session_search-metadata']);
    unset($_SESSION['session_search-pdfs']);
    unset($_SESSION['session_search-notes']);
    unset($_SESSION['session_include-categories']);
    unset($_SESSION['rating[]']);
    die();
}

$limit = $_SESSION['limit'] = 10;

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

if (!isset($_SESSION['orderby'])) {
    $orderby = $_SESSION['orderby'] = 'id';
} else {
    $orderby = $_SESSION['orderby'];
}

if (!isset($_SESSION['display'])) {
    $display = $_SESSION['display'] = 'title';
} else {
    $display = $_SESSION['display'];
}

if (empty($_GET['select']) ||
        ($_GET['select'] != 'library' &&
        $_GET['select'] != 'shelf' &&
        $_GET['select'] != 'desk' &&
        $_GET['select'] != 'clipboard')) {

    $_GET['select'] = 'library';
}

if (!empty($_GET['searchmode'])) {
    
     ######## REGISTER SESSION VARIABLES ##############

    $get_array = array('anywhere', 'authors', 'affiliation', 'title', 'journal', 'secondary_title', 'abstract', 'keywords',
        'year', 'fulltext', 'notes', 'category', 'whole_words', 'case', 'rating', 'search_id', 'search-metadata', 'search-pdfs', 'search-notes', 'include-categories');

    while (list($key, $index) = each($get_array)) {

        if (isset($_GET[$index]) && !empty($_GET[$index])) {

            ${$index} = $_GET[$index];
            $_SESSION['session_' . $index] = $_GET[$index];

            if ($index != 'category' && $index != 'year' && $index != 'whole_words' && $index != 'case' && $index != 'rating' && $index != 'search_id'
                && $index != 'search-metadata' && $index != 'search-pdfs' && $index != 'search-notes' && $index != 'include-categories') {

                ${$index} = $_GET[$index];
                ${$index . '_separator'} = $_GET[$index . '_separator'];
                $_SESSION['session_' . $index . '_separator'] = $_GET[$index . '_separator'];
            }
        } else {

            ${$index} = '';
            unset($_SESSION['session_' . $index]);

            if ($index != 'category' && $index != 'year' && $index != 'whole_words' && $index != 'case' && $index != 'rating' && $index != 'search_id'
                && $index != 'search-metadata' && $index != 'search-pdfs' && $index != 'search-notes' && $index != 'include-categories') {
                ${$index . '_separator'} = '';
                unset($_SESSION['session_' . $index . '_separator']);
            }
        }
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
    
// FILE CACHING
    
    $db_change = database_change(array(
        'library',
        'shelves',
        'projects',
        'projectsusers',
        'projectsfiles',
        'filescategories',
        'notes'
    ), array(
        'full_text'
    ), array('clipboard'));
    
    if (isset($_GET['from'])) {
        $cache_name = cache_name();
        cache_start($db_change);
    }
    
    //READ SHELF AND PROJECTS

    database_connect(IL_DATABASE_PATH, 'library');

    $desktop_projects = array();
    $desktop_projects = read_desktop($dbHandle);

    ######### SHELF, CLIPBOARD, DESK ################

    $in = '';

    if ($_GET['select'] == 'shelf') {
        $in = "id IN (SELECT fileID FROM shelves WHERE userID=" . intval($_SESSION['user_id']) . ") AND";
    }

    if ($_GET['select'] == 'desk') {
        $project_name = '';
        $in = 'WHERE id IN () AND ';
        if (is_numeric($_GET['project'])) {
            $result = $dbHandle->query("SELECT fileID FROM projectsfiles WHERE projectID=" . intval($_GET['project']));
            $project_files = $result->fetchAll(PDO::FETCH_COLUMN);
            $project_files = implode(',', $project_files);
            $in = "id IN (" . $project_files . ") AND ";
            $result = null;
            $result = $dbHandle->query("SELECT project FROM projects WHERE projectID=" . intval($_GET['project']));
            $project_name = $result->fetchColumn();
            $result = null;
        }
    }

    if ($_GET['select'] == 'clipboard') {
        $in = "id IN (SELECT id FROM clipboard.files) AND";
    }

    $ordering = 'ORDER BY ' . $orderby . ' COLLATE NOCASE ASC';
    if ($orderby == 'year' || $orderby == 'addition_date' || $orderby == 'rating' || $orderby == 'id')
        $ordering = 'ORDER BY ' . $orderby . ' DESC';

    $anywhere_array = array();
    $authors_array = array();
    $title_array = array();
    $journal_array = array();
    $secondary_title_array = array();
    $abstract_array = array();
    $keywords_array = array();
    $affiliation_array = array();
    $search_id_array = array();
    $year_array = array();
    $fulltext_array = array();
    $notes_array = array();
    $rating_array = array();
    $case2 = 0;


    if (isset($_GET['searchmode']) && $_GET['searchmode'] == 'advanced') {
        include '../advancedsearch.php';
    }
    
    //SEARCH
    
    $result_array = array();
    $rows = 0;
    
    if (!empty($search_string) && $_GET['searchtype'] == 'metadata') {

        $dbHandle->sqliteCreateFunction('regexp_match', 'sqlite_regexp', 3);
        
        $dbHandle->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_SILENT);
        perform_search("SELECT id FROM library WHERE $in $search_string $ordering");
    }
    
    //PRE-FETCH CATEGORIES, PROJECTS FOR DISPLAYED ITEMS IN A BATCH INTO TEMP DATABASE TO OFFLOAD THE MAIN DATABASE
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
    }

    $microtime2 = microtime(true);
    $microtime = $microtime2 - $microtime1;
    $microtime = sprintf("%01.2f sec", $microtime);

    if ($rows > 0) print '<div id="display-content" class="ui-content">';

    $url_array = array();
    reset($_GET);
    while (list($name, $value) = each($_GET)) {
        if (is_array($_GET[$name])) {
            while (list(, $value2) = each($_GET[$name])) {
                if (!empty($value2))
                    $url_array[] = urlencode($name . '[]') . '=' . urlencode($value2);
            }
        } elseif ($name != 'from') {
            if (!empty($value))
                $url_array[] = urlencode($name) . '=' . urlencode($value);
        }
    }
    $url_string = join('&', $url_array);

    if ($rows > 0) {

        $items_from = $from + 1;
        (($from + $limit) > $rows) ? $items_to = $rows : $items_to = $from + $limit;

        print '<div style="padding-top:7px;text-align:center;font-size:0.85em">Search results ' . $items_from . '-' . $items_to . ' of <span id="total-items">' . $rows . '</span> in ' . $microtime . '.</div>';

        mobile_show_search_results($result, $clip_files);

    } else {
        print '<div style="padding-top:100px;color:#bfbeb9;font-size:28px;text-align:center"><b>No Hits</b></div>';
    }
    print '</div>';
    
    if (isset($_GET['from']) && $_GET['from'] > 0) cache_store();
}
?>