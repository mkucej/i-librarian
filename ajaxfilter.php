<?php

include_once 'data.php';
include_once 'functions.php';
session_write_close();

if (!isset($_GET['select']) || ($_GET['select'] != 'library' &&
        $_GET['select'] != 'shelf' &&
        $_GET['select'] != 'project' &&
        $_GET['select'] != 'clipboard')) {

    $_GET['select'] = 'library';
}

database_connect(IL_DATABASE_PATH, 'library');

$in = '';

if ($_GET['select'] == 'shelf') {
    $in = "id IN (SELECT fileID FROM shelves WHERE fileID>0 AND userID=" . intval($_SESSION['user_id']) . ")";
}

if ($_GET['select'] == 'clipboard') {
    attach_clipboard($dbHandle);
    $in = "id IN (SELECT id FROM clipboard.files)";
}

empty($in) ? $and = '' : $and = 'AND';

if (isset($_GET['term']))
    $_GET['filter'] = $_GET['term'];

if (isset($_GET['filter'])) {
    $filter = $_GET['filter'];
    $filter_query = $dbHandle->quote("%$filter%");
} else {
    die();
}

######################################################################

if (isset($_GET['open']) && in_array("authors", $_GET['open'])) {

    $filter_arr = array();
    if (strstr($filter, ',') !== 0)
        $filter_arr = explode(',', $filter);
    if (!empty($filter_arr[0]))
        $author_filter = $dbHandle->quote('%L:"' . trim($filter_arr[0]) . '%');
    if (!empty($filter_arr[1]))
        $author_filter = $dbHandle->quote('%L:"' . trim($filter_arr[0]) . '",F:"' . trim($filter_arr[1]) . '%');

    $result = $dbHandle->query("SELECT authors || ';' || authors_ascii FROM library WHERE $in $and (authors LIKE $author_filter OR authors_ascii LIKE $author_filter)");
    $authors = $result->fetchAll(PDO::FETCH_COLUMN);

    $dbHandle = null;

    $authors_string = '';

    $authors_string = implode(";", $authors);
    $authors = explode(";", $authors_string);

    function filter_authors($var) {
        global $filter_arr;
        return stripos($var, 'L:"' . trim($filter_arr[0])) === 0;
    }

    $authors = array_filter($authors, 'filter_authors');

    if (empty($authors)) {
        if (isset($_GET['term'])) {
            echo json_encode(array());
        } else {
            echo 'No such authors.';
        }
        die();
    }

    $authors_unique = array_unique($authors);
    usort($authors_unique, "strnatcasecmp");

    $json_authors = array();

    while (list($key, $authors) = each($authors_unique)) {
        $authors = str_replace('L:"', '', $authors);
        $authors = str_replace('",F:"', ', ', $authors);
        $authors = substr($authors, 0, -1);
        $json_authors[] = $authors;
        if (!isset($_GET['term'])) print PHP_EOL . '<span class="author" id="' . urlencode($authors) . '">' . htmlspecialchars($authors) . '</span><br>';
    }

    // autocomplete
    if (isset($_GET['term']))
        echo json_encode($json_authors);
}

######################################################################

if (isset($_GET['open']) && in_array("keywords", $_GET['open'])) {

    $result = $dbHandle->query("SELECT keywords FROM library WHERE $in $and keywords LIKE $filter_query");
    $keywords = $result->fetchAll(PDO::FETCH_COLUMN);
    $dbHandle = null;

    $keywords_string = '';
    $keywords_string = implode("/", $keywords);
    $keywords = explode("/", $keywords_string);

    foreach ($keywords as $value) {
        $trimmed_keywords[] = trim($value);
    }

    $trimmed_keywords = array_filter($trimmed_keywords);

    if (empty($trimmed_keywords)) {
        print 'No such keywords.';
        die();
    }

    $keywords_array = array_unique($trimmed_keywords);
    usort($keywords_array, "strnatcasecmp");

    while (list($key, $keywords) = each($keywords_array)) {
        if (!empty($keywords) && stripos($keywords, $filter) !== false) {
            print '<span class="key" id="' . htmlspecialchars(urlencode($keywords)) . '">' . htmlspecialchars($keywords) . '</span><br>';
        }
    }
}

######################################################################

if (isset($_GET['open']) && in_array("editors", $_GET['open'])) {

    $filter_arr = array();
    if (strstr($filter, ',') !== 0)
        $filter_arr = explode(',', $filter);
    if (!empty($filter_arr[0]))
        $author_filter = $dbHandle->quote('%L:"' . trim($filter_arr[0]) . '%');
    if (!empty($filter_arr[1]))
        $author_filter = $dbHandle->quote('%L:"' . trim($filter_arr[0]) . '",F:"' . trim($filter_arr[1]) . '%');

    $result = $dbHandle->query("SELECT editor FROM library WHERE $in $and (editor LIKE $author_filter)");
    $authors = $result->fetchAll(PDO::FETCH_COLUMN);

    $dbHandle = null;

    $authors_string = '';

    $authors_string = implode(";", $authors);
    $authors = explode(";", $authors_string);

    function filter_authors($var) {
        global $filter_arr;
        return stripos($var, 'L:"' . trim($filter_arr[0])) === 0;
    }

    $authors = array_filter($authors, 'filter_authors');

    if (empty($authors)) {
        if (isset($_GET['term'])) {
            echo json_encode(array());
        } else {
            echo 'No such editors.';
        }
        die();
    }

    $authors_unique = array_unique($authors);
    usort($authors_unique, "strnatcasecmp");

    $json_authors = array();

    while (list($key, $authors) = each($authors_unique)) {
        $authors = str_replace('L:"', '', $authors);
        $authors = str_replace('",F:"', ', ', $authors);
        $authors = substr($authors, 0, -1);
        $json_authors[] = $authors;
        if (!isset($_GET['term'])) print PHP_EOL . '<span class="author" id="' . urlencode($authors) . '">' . htmlspecialchars($authors) . '</span><br>';
    }

    // autocomplete
    if (isset($_GET['term']))
        echo json_encode($json_authors);
}
?>