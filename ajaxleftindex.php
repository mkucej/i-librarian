<?php

include_once 'data.php';
include_once 'functions.php';
session_write_close();

// CACHING

$cache_name = cache_name();
$db_change = database_change(array(
    'library',
    'shelves',
    'filescategories',
    'searches',
    'categories'
        ), array(), array('clipboard'));
cache_start($db_change);

if (!isset($_GET['select']))
    $_GET['select'] = 'library';

if ($_GET['select'] != 'library' &&
        $_GET['select'] != 'shelf' &&
        $_GET['select'] != 'project' &&
        $_GET['select'] != 'clipboard') {

    $_GET['select'] = 'library';
}

database_connect(IL_DATABASE_PATH, 'library');

$in = '';

if ($_GET['select'] == 'shelf') {
    $in = "INNER JOIN shelves ON library.id=shelves.fileID WHERE shelves.userID=" . intval($_SESSION['user_id']);
}

if ($_GET['select'] == 'clipboard') {
    attach_clipboard($dbHandle);
    $in = "WHERE id IN (SELECT id FROM clipboard.files)";
}

empty($in) ? $where = 'WHERE' : $where = 'AND';

### CATEGORY ###################################################################

if (isset($_GET['open']) && in_array("category", $_GET['open'])) {

    $subfiles = '';

    if ($_GET['select'] == 'shelf' || $_GET['select'] == 'clipboard') {
        $dbHandle->exec("PRAGMA temp_store = MEMORY");
        $dbHandle->exec("PRAGMA synchronous = OFF");
        $dbHandle->exec("CREATE TEMPORARY TABLE subfiles (fileID INTEGER PRIMARY KEY)");
        $subfiles = "filescategories.fileID IN (SELECT fileID FROM subfiles)";
    }

    if ($_GET['select'] == 'shelf') {
        $dbHandle->exec("INSERT INTO subfiles SELECT fileID FROM shelves WHERE userID=" . intval($_SESSION['user_id']) . "");
    }

    if ($_GET['select'] == 'clipboard') {

        attach_clipboard($dbHandle);
        $dbHandle->exec("INSERT INTO subfiles (fileID) SELECT id FROM clipboard.files");
        }

    $categories_to_search = array_keys($_GET['open'], 'category');

    ###first level open###

    if (empty($categories_to_search[0])) {

        if (!empty($subfiles))
            $subfiles = "WHERE " . $subfiles;

        if ($_GET['select'] == 'library') {
            $result = $dbHandle->query("SELECT categoryID,category
			FROM categories 
			ORDER BY category COLLATE NOCASE ASC");
        } elseif ($_GET['select'] == 'shelf' || $_GET['select'] == 'clipboard') {
            $result = $dbHandle->query("SELECT DISTINCT categories.categoryID AS categoryID,category
			FROM filescategories
			INNER JOIN categories ON filescategories.categoryID=categories.categoryID
			$subfiles
			ORDER BY category COLLATE NOCASE ASC");
        }

        print '<div>';

        print PHP_EOL . '<div id="cat-0"><span class="cat1">!unassigned</span><div></div></div>';

        while ($categories = $result->fetch(PDO::FETCH_ASSOC)) {

            print PHP_EOL . '<div id="cat-' . $categories['categoryID'] . '"><span class="cat1">' . htmlspecialchars($categories['category'])
                    . '</span><div style="margin-left:15px;white-space: nowrap"></div></div>';
        }

        print '</div>';
    }

    ###second level open###

    if (!empty($categories_to_search[0]) && empty($categories_to_search[1]) && empty($categories_to_search[2])) {

        if (!empty($subfiles))
            $subfiles = $subfiles . " AND";

        $category_query = $dbHandle->quote($categories_to_search[0]);

        $result = $dbHandle->query("SELECT categoryID,category
			FROM categories
			WHERE categoryID IN (SELECT categoryID
				FROM filescategories
				WHERE $subfiles fileID IN (SELECT fileID FROM filescategories WHERE categoryID=$category_query)
					AND NOT categoryID IN ($category_query))
			ORDER BY category COLLATE NOCASE ASC");

        $lines = array();

        while ($categories = $result->fetch(PDO::FETCH_ASSOC)) {

            $is_category = '';

            print PHP_EOL . '<div id="cat-' . $categories['categoryID'] . '"><span class="cat2">- ' . htmlspecialchars($categories['category'])
                    . '</span><div style="margin-left:20px;white-space:nowrap"></div></div>';
        }

        if (!isset($is_category)) {
            print 'No further categories.';
            die();
        }
    }

    ###third level open###

    if (!empty($categories_to_search[0]) && !empty($categories_to_search[1]) && empty($categories_to_search[2])) {

        if (!empty($subfiles))
            $subfiles = $subfiles . " AND";

        $category_query1 = $dbHandle->quote($categories_to_search[0]);
        $category_query2 = $dbHandle->quote($categories_to_search[1]);

        $result = $dbHandle->query("SELECT categoryID,category
			FROM categories
			WHERE categoryID IN (SELECT categoryID
				FROM filescategories
				WHERE $subfiles fileID IN (SELECT fileID FROM filescategories WHERE categoryID=$category_query1 INTERSECT SELECT fileID FROM filescategories WHERE categoryID=$category_query2)
					AND NOT categoryID IN ($category_query1,$category_query2))
			ORDER BY category COLLATE NOCASE ASC");

        while ($categories = $result->fetch(PDO::FETCH_ASSOC)) {

            $is_category = '';

            print PHP_EOL . '<div id="cat-' . $categories['categoryID'] . '"><span class="cat3"><b>&middot;</b> ' . htmlspecialchars($categories['category'])
                    . '</span><div style="margin-left:20px;white-space:nowrap"></div></div>';
        }

        if (!isset($is_category)) {
            print 'No further categories.';
            die();
        }
    }

    ###fourth level open###

    if (!empty($categories_to_search[0]) && !empty($categories_to_search[1]) && !empty($categories_to_search[2])) {

        if (!empty($subfiles))
            $subfiles = $subfiles . " AND";

        $category_query1 = $dbHandle->quote($categories_to_search[0]);
        $category_query2 = $dbHandle->quote($categories_to_search[1]);
        $category_query3 = $dbHandle->quote($categories_to_search[2]);

        $result = $dbHandle->query("SELECT categoryID,category
			FROM categories
			WHERE categoryID IN (SELECT categoryID
				FROM filescategories
				WHERE $subfiles fileID IN (SELECT fileID FROM filescategories WHERE categoryID=$category_query1
								INTERSECT SELECT fileID FROM filescategories WHERE categoryID=$category_query2
								INTERSECT SELECT fileID FROM filescategories WHERE categoryID=$category_query3)
				AND NOT categoryID IN ($category_query1,$category_query2,$category_query3))
			ORDER BY category COLLATE NOCASE ASC");

        while ($categories = $result->fetch(PDO::FETCH_ASSOC)) {

            $is_category = '';

            print PHP_EOL . '<span class="cat4" id="cat-' . $categories['categoryID'] . '">- ' . htmlspecialchars($categories['category']) . '</span><br>';
        }

        if (!isset($is_category)) {
            print 'No further categories.';
            die();
        }
    }
}

### ADDITION DATES ############################################################

if (isset($_GET['open']) && in_array("dates", $_GET['open'])) {

    $date_array = array();
    $phpdate_number = array();
    $json = array(
        'mindate' => '',
        'maxdate' => '',
        'datecount' => array()
    );

    $result = $dbHandle->query("SELECT addition_date,count(*) FROM library $in GROUP BY addition_date ORDER BY addition_date DESC LIMIT 60");

    while ($fetch = $result->fetch(PDO::FETCH_NUM)) {
        $date_array[] = $fetch[0];
        $phpdate_number[$fetch[0]] = $fetch[1];
    }

    $dbHandle = null;

    if (!empty($date_array)) {
        $json['mindate'] = end($date_array);
        $json['maxdate'] = reset($date_array);
        $json['datecount'] = $phpdate_number;
    }
    
    print json_encode($json);
    die();
}

### AUTHORS ###################################################################

if (isset($_GET['open']) && in_array("authors", $_GET['open'])) {

    
    if (isset($_GET['first_letter']) && ctype_alpha($_GET['first_letter'])) {
        $first_letter = $_GET['first_letter'];
    } else {
        $first_letter = 'a';
    }

    $from = 0;
    if (isset($_GET['from']))
        $from = intval($_GET['from']);

    $result = $dbHandle->query("SELECT authors FROM library $in");
    $authors = $result->fetchAll(PDO::FETCH_COLUMN);
    $dbHandle = null;

    $authors_string = '';
    $authors_string = implode(";", $authors);
    $authors = explode(";", $authors_string);
    
    function filter_authors($var) {
        global $first_letter;
        if (!empty($var) && stripos($var, 'L:"'.$first_letter) === 0) {
            return true;
        } elseif (!empty($var) && $first_letter == 'All') {
            return true;
        }
    }

    $authors = array_filter($authors, 'filter_authors');

    if (empty($authors)) {
        print '<div>No authors starting with ' . strtoupper($first_letter) . '.</div>';
        die();
    }

    $authors_array = array_unique($authors);
    usort($authors_array, "strnatcasecmp");

    $i = 0;
    $isauthor = null;

    print '<div>';
    
    while (list($key, $authors) = each($authors_array)) {
            if ($i >= $from && $i < ($from + 1000)) {
                $authors = str_replace('L:"', '', $authors);
                $authors = str_replace('",F:"', ', ', $authors);
                $authors = substr($authors, 0, -1);
                print '<span class="author" id="' . urlencode($authors) . '">' . htmlspecialchars($authors) . '</span><br>';
            }
            $i = $i + 1;
            
        if ($i > ($from + 1000))
            break;
    }

    print '</div>';
}

### JOURNALS ###################################################################

if (isset($_GET['open']) && in_array("journal", $_GET['open'])) {

    $journal_to_browse = array_keys($_GET['open'], 'journal');

    if (empty($journal_to_browse[0])) {

        $output = '';

        $result = $dbHandle->query("SELECT DISTINCT journal FROM library $in ORDER BY journal COLLATE NOCASE ASC");
        $dbHandle = null;

        while ($fetch = $result->fetch(PDO::FETCH_NUM)) {

            if (!empty($fetch[0])) {

                $output .= PHP_EOL . '<div id="' . urlencode($fetch[0]) . '"><span class="jour">'
                        . htmlspecialchars($fetch[0]) . '</span><div style="display:none;margin-left:20px;white-space: nowrap"></div></div>';
            }
        }

        if (empty($output)) {
            print "No journals.";
            die();
        }

        print "<div>$output</div>";
    }

    if (!empty($journal_to_browse[0])) {

        $output = '';

        $journal_query = $dbHandle->quote($journal_to_browse[0]);

        $result = $dbHandle->query("SELECT DISTINCT CAST(year AS DATE) as y FROM library $in $where journal=$journal_query ORDER BY y DESC");
        $dbHandle = null;

        while ($fetch = $result->fetch(PDO::FETCH_NUM)) {

            if (!empty($fetch[0])) {

                $output .= PHP_EOL . '<span class="jour2">' . htmlspecialchars($fetch[0]) . '</span><br>';
            }
        }

        if (empty($output)) {
            print "No journals.";
            die();
        }

        print "<div>$output</div>";
    }
}


### SECONDARY TITLES ###################################################################

if (isset($_GET['open']) && in_array("secondary_title", $_GET['open'])) {

    $secondary_title_to_browse = array_keys($_GET['open'], 'secondary_title');

    if (empty($secondary_title_to_browse[0])) {

        $output = '';

        $result = $dbHandle->query("SELECT DISTINCT secondary_title FROM library $in ORDER BY secondary_title COLLATE NOCASE ASC");
        $dbHandle = null;

        while ($fetch = $result->fetch(PDO::FETCH_NUM)) {

            if (!empty($fetch[0])) {

                $output .= PHP_EOL . '<div id="' . urlencode($fetch[0]) . '"><span class="sec">'
                        . htmlspecialchars($fetch[0]) . '</span><div style="display:none;margin-left:20px;white-space: nowrap"></div></div>';
            }
        }

        if (empty($output)) {
            print "No secondary titles.";
            die();
        }

        print "<div>$output</div>";
    }

    if (!empty($secondary_title_to_browse[0])) {

        $output = '';

        $secondary_title_query = $dbHandle->quote($secondary_title_to_browse[0]);

        $result = $dbHandle->query("SELECT DISTINCT CAST(year AS DATE) as y FROM library $in $where secondary_title=$secondary_title_query ORDER BY y DESC");
        $dbHandle = null;

        while ($fetch = $result->fetch(PDO::FETCH_NUM)) {

            if (!empty($fetch[0])) {

                $output .= PHP_EOL . '<span class="sec2">' . htmlspecialchars($fetch[0]) . '</span><br>';
            }
        }

        if (empty($output)) {
            print "No secondary titles.";
            die();
        }

        print "<div>$output</div>";
    }
}

### TERTIARY TITLES ###################################################################

if (isset($_GET['open']) && in_array("tertiary_title", $_GET['open'])) {

    $tertiary_title_to_browse = array_keys($_GET['open'], 'tertiary_title');

    if (empty($tertiary_title_to_browse[0])) {

        $output = '';

        $result = $dbHandle->query("SELECT DISTINCT tertiary_title FROM library $in ORDER BY tertiary_title COLLATE NOCASE ASC");
        $dbHandle = null;

        while ($fetch = $result->fetch(PDO::FETCH_NUM)) {

            if (!empty($fetch[0])) {

                $output .= PHP_EOL . '<div id="' . urlencode($fetch[0]) . '"><span class="sec">'
                        . htmlspecialchars($fetch[0]) . '</span><div style="display:none;margin-left:20px;white-space: nowrap"></div></div>';
            }
        }

        if (empty($output)) {
            print "No tertiary titles.";
            die();
        }

        print "<div>$output</div>";
    }

    if (!empty($tertiary_title_to_browse[0])) {

        $output = '';

        $tertiary_title_query = $dbHandle->quote($tertiary_title_to_browse[0]);

//        $result = $dbHandle->query("SELECT DISTINCT CAST(year AS DATE) as y FROM library $in $where tertiary_title=$tertiary_title_query ORDER BY y DESC");
        $result = $dbHandle->query("SELECT DISTINCT secondary_title FROM library $in $where tertiary_title=$tertiary_title_query ORDER BY secondary_title ASC");
        $dbHandle = null;

        while ($fetch = $result->fetch(PDO::FETCH_NUM)) {

            if (!empty($fetch[0])) {

                $output .= PHP_EOL . '<span class="sec2">' . htmlspecialchars($fetch[0]) . '</span><br>';
            }
        }

        if (empty($output)) {
            print "No tertiary titles.";
            die();
        }

        print "<div>$output</div>";
    }
}

### KEYWORDS ###################################################################

if (isset($_GET['open']) && in_array("keywords", $_GET['open'])) {

    $from = 0;
    if (isset($_GET['from']))
        $from = intval($_GET['from']);

    $result = $dbHandle->query("SELECT keywords FROM library $in");
    $keywords = $result->fetchAll(PDO::FETCH_COLUMN);

    $keywords_string = '';
    $keywords_string = implode("/", $keywords);
    $keywords = explode("/", $keywords_string);

    foreach ($keywords as $value) {
        $trimmed_keywords[] = trim($value);
    }

    $trimmed_keywords = array_filter($trimmed_keywords);

    $keywords_array = array_unique($trimmed_keywords);

    if (empty($keywords_array)) {
        print 'No keywords.';
        die();
    }

    if ($from > count($keywords_array)) {
        print 'No further keywords.';
        die();
    }

    usort($keywords_array, "strnatcasecmp");
    $keywords_array = array_slice($keywords_array, $from, 1000, true);

    print '<div>';

    while (list($key, $keywords) = each($keywords_array)) {
        if (!empty($keywords)) {
            print '<span class="key" id="' . htmlspecialchars(urlencode($keywords)) . '">' . htmlspecialchars($keywords) . '</span><br>';
        }
    }

    print '</div>';
}

### SAVED SEARCHES ###################################################################

if (isset($_GET['open']) && in_array("savedsearch", $_GET['open'])) {

    $result = $dbHandle->query("SELECT DISTINCT searchname FROM searches WHERE userID=" . intval($_SESSION['user_id']) . " AND searchname LIKE 'advancedsearch#%'");
    $searches = $result->fetchAll(PDO::FETCH_COLUMN);

    print '<div>';

    if (!empty($searches)) {

        foreach ($searches as $search) {
            $search = substr($search, 15);
            print '<div><div class="savedsearch" id="' . rawurlencode($search) . '">' . htmlspecialchars($search) . '</div>';
            print '<button class="rename-search"><i class="fa fa-pencil"></i> Rename</button> <button class="delete-search"><i class="fa fa-trash-o"></i> Delete</button></div>';
        }
    } else {
        print 'No saved searches.';
    }

    print '</div>';
}

cache_store();
?>