<?php

// Extract and Record PDF full text.
function recordFulltext($id, $file_name) {

    if (!is_numeric($id)) {
        return "Warning! Invalid item ID.";
    }

    $file_name = preg_replace('/[^a-zA-z0-9\_\.pdf]/', '', $file_name);

    system(select_pdftotext() . ' -enc UTF-8 '
        . escapeshellarg(IL_PDF_PATH . DIRECTORY_SEPARATOR . get_subfolder($file_name) . DIRECTORY_SEPARATOR . $file_name)
        . ' ' . escapeshellarg(IL_TEMP_PATH . DIRECTORY_SEPARATOR . $file_name . '.txt'));

    if (!is_file(IL_TEMP_PATH . DIRECTORY_SEPARATOR . $file_name . ".txt")) {
        return "No text found in this PDF.";
    }

    $string = file_get_contents(IL_TEMP_PATH . DIRECTORY_SEPARATOR . $file_name . ".txt");
    unlink(IL_TEMP_PATH . DIRECTORY_SEPARATOR . $file_name . ".txt");

    // Replace line breaks with spaces.
    $order = array("\r\n", "\n", "\r");
    $string = str_replace($order, ' ', $string);

    // Strip invalid UTF-8 characters.
    $string = preg_replace('/[^\x{0009}\x{000a}\x{000d}\x{0020}-\x{D7FF}\x{E000}-\x{FFFD}]+/u', ' ', $string);
    $string = preg_replace('/\s{2,}/ui', ' ', $string);

    // Strip non-printing characters.
    $string = trim(filter_var($string, FILTER_SANITIZE_STRING, FILTER_FLAG_STRIP_LOW));

    $dbHandle = database_connect(IL_DATABASE_PATH, 'fulltext');

    $id_query = $dbHandle->quote($id);
    $fulltext_query = $dbHandle->quote($string);

    $dbHandle->beginTransaction();

    $dbHandle->exec("DELETE FROM full_text WHERE fileID=$id_query");

    // Only record non-empty string.
    // If only spaces.
    $srting2 = trim($string);

    if (!empty($srting2)) {

        $dbHandle->exec("INSERT INTO full_text (fileID,full_text) VALUES ($id_query,$fulltext_query)");
        $dbHandle->commit();
        $dbHandle = null;

    } else {

        $dbHandle->commit();
        $dbHandle = null;
        return "This PDF cannot be indexed for full text search.";
    }
}

// Send error message to client via AJAX.
function sendError($errorMessage, $statusCode = 500) {

    $errorMessage = preg_replace('/[\"\(\)\<\>\@\,\;\:\/\[\]\?\=\{\}\\\]*/', '', $errorMessage);
    header('Error-Message: ' . $errorMessage, true, $statusCode);
    die();

}

// Send error message to client via AJAX.
function displayError($errorMessage) {

    die("<h3 style=\"text-align:center\">Error! $errorMessage</h3>");

}

// get numbered subfolder where file resides
function get_subfolder($file, $dir = null) {

    $id = substr($file, 0, 5);

    // If just numeric id is passed.
    if (is_numeric($id) && strlen($id) < 5) {

        $id = str_pad($id, 5, '0', STR_PAD_LEFT);
    }

    if (is_numeric($id) && strlen($id) === 5) {

        $level_1 = substr($id, 0, 1);
        $level_2 = substr($id, 1, 1);

        // Create the dir, if it does not exist.
        if (isset($dir)) {

            $dir .= DIRECTORY_SEPARATOR . $level_1 . DIRECTORY_SEPARATOR . $level_2;

            if (!is_dir($dir)) {

                mkdir($dir, 0755, true);
            }
        }

        return $level_1 . DIRECTORY_SEPARATOR . $level_2;

    } else {

        return false;
    }
}

// Search database and write to history cache tables.
function perform_search($sql) {

    global $dbHandle;
    global $limit;
    global $from;
    global $orderby;
    global $ordering;
    global $result;
    global $rows;
    global $case;

    // Generate table name hash.

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

    // Read db change times.
    $db_change = database_change(array(
        'library',
        'shelves',
        'projects',
        'projectsusers',
        'projectsfiles',
        'filescategories',
        'notes',
        'annotations'
            ), array('full_text'), array('clipboard'));

    $quoted_path = $dbHandle->quote(IL_DATABASE_PATH . DIRECTORY_SEPARATOR . 'history.sq3');
    $dbHandle->exec("ATTACH DATABASE $quoted_path as history");

    if (isset($_GET['select']) && $_GET['select'] == 'clipboard') {
        attach_clipboard($dbHandle);
    }

    $quoted_path = $dbHandle->quote(IL_DATABASE_PATH . DIRECTORY_SEPARATOR . 'fulltext.sq3');

    if ((isset($_GET['browse']) && array_key_exists('Not Indexed', $_GET['browse'])) || (isset($_GET['searchtype']) && $_GET['searchtype'] == 'pdf') || (isset($_GET['searchtype']) && $_GET['searchtype'] == 'global')) {
        $dbHandle->exec("ATTACH DATABASE $quoted_path AS fulltextdatabase");
    }

    $quoted_path = $dbHandle->quote(IL_DATABASE_PATH . DIRECTORY_SEPARATOR . 'discussions.sq3');

    if (isset($_GET['browse']) && array_key_exists('Discussed Items', $_GET['browse'])) {
        $dbHandle->exec("ATTACH DATABASE $quoted_path AS discussionsdatabase");
    }

    $dbHandle->exec("CREATE TABLE IF NOT EXISTS history.search_tables ("
            . "id INTEGER PRIMARY KEY,"
            . "table_name TEXT NOT NULL DEFAULT '',"
            . "created TEXT NOT NULL DEFAULT '',"
            . "total_rows TEXT NOT NULL DEFAULT '')");

    // Delete stale tables.
    $dbHandle->beginTransaction();

    $result = $dbHandle->query("SELECT id, table_name, created FROM history.search_tables");
    $rows = $result->fetchAll(PDO::FETCH_ASSOC);
    $result = null;

    foreach ($rows as $row) {

        if ($row['created'] < $db_change || isset($_GET['browse']['Discussed Items']) || isset($_GET['browse']['Viewed in the last 8 hours'])) {

            $dbHandle->exec("DROP TABLE history.`" . $row['table_name'] . "`");
            $dbHandle->exec("DELETE FROM history.search_tables WHERE id=" . $row['id']);
        }
    }

    $dbHandle->commit();

    $rows = null;

    // Check if cache table exists.
    $result = $dbHandle->query("SELECT count(*) FROM history.sqlite_master WHERE type='table' AND name='$table_name_hash'");
    $table = $result->fetchColumn();
    $result = null;

    // No. Get all ids ordered + total count and save to history.
    // Yes. Do id-based paging.
    if ($table == 0) {

        $dbHandle->sqliteCreateFunction('regexp_match', 'sqlite_regexp', 3);

        if ($case == 1) {
            $dbHandle->exec("PRAGMA case_sensitive_like = 1");
        }

        $dbHandle->exec("CREATE TABLE IF NOT EXISTS history.`" . $table_name_hash . "` "
                . "(id INTEGER PRIMARY KEY, itemID INTEGER NOT NULL DEFAULT '')");

        $dbHandle->beginTransaction();

        $dbHandle->exec("INSERT INTO history.`$table_name_hash` (itemID) " . $sql);

        $dbHandle->exec("INSERT INTO history.search_tables(table_name,created,total_rows)"
                . " VALUES('$table_name_hash', '" . time() . "', (SELECT count(*) FROM history.`$table_name_hash`))");

        $dbHandle->commit();
    }

    $result = $dbHandle->query("SELECT id,file,authors,title,journal,secondary_title,year,volume,pages,abstract,uid,doi,url,addition_date,rating,bibtex
        FROM library WHERE id IN (SELECT itemID FROM history.`$table_name_hash` LIMIT $limit OFFSET $from) $ordering");

    $result2 = $dbHandle->query("SELECT total_rows FROM history.search_tables WHERE table_name='$table_name_hash'");
    $rows = $result2->fetchColumn();
    $result2 = null;

    $dbHandle->exec("DETACH history");
    $dbHandle->exec("DETACH clipboard");
    $dbHandle->exec("DETACH fulltextdatabase");
    $dbHandle->exec("PRAGMA shrink_memory");

}

// Attach clipboard database.
function attach_clipboard($dbHandle) {

    $clipboard_db = $dbHandle->quote(IL_TEMP_PATH . DIRECTORY_SEPARATOR . 'lib_' . session_id() . DIRECTORY_SEPARATOR . 'clipboard.sq3');
    $dbHandle->exec("ATTACH DATABASE $clipboard_db AS clipboard");
    $dbHandle->exec("CREATE TABLE IF NOT EXISTS clipboard.files (id INTEGER PRIMARY KEY)");
    $dbHandle->exec("CREATE TABLE IF NOT EXISTS clipboard.clipboard_log (ch_time TEXT NOT NULL DEFAULT '')");
    $dbHandle->exec("INSERT OR IGNORE INTO clipboard.clipboard_log (rowid, ch_time) VALUES(1, strftime('%s','now'))");
    $dbHandle->exec("CREATE TRIGGER IF NOT EXISTS clipboard.trigger_clipboard_delete AFTER DELETE ON files
                        BEGIN
                            UPDATE clipboard_log SET ch_time=strftime('%s','now') WHERE rowid=1;
                        END;");
    $dbHandle->exec("CREATE TRIGGER IF NOT EXISTS clipboard.trigger_clipboard_insert AFTER INSERT ON files
                        BEGIN
                            UPDATE clipboard_log SET ch_time=strftime('%s','now') WHERE rowid=1;
                        END;");

}

// ALLOW SUB, SUP, AND MATHML
function lib_htmlspecialchars($input) {
    $input = htmlspecialchars($input);
    $arr = array('span', 'math', 'maction', 'maligngroup', 'malignmark', 'menclose', 'merror',
        'mfenced', 'mfrac', 'mglyph', 'mi', 'mlabeledtr', 'mlongdiv',
        'mmultiscripts', 'mn', 'mo,mover', 'mpadded', 'mphantom', 'mroot', 'mrow',
        'ms', 'mscarries', 'mscarry', 'msgroup', 'msline', 'mspace', 'msqrt', 'msrow',
        'mstack', 'mstyle', 'msub', 'msup', 'msubsup', 'mtable', 'mtd', 'mtext', 'mtr',
        'munder', 'munderover', 'sub', 'sup');
    foreach ($arr as $tag) {
        $input = str_replace('&lt;span class=&quot;highlight-search&quot;&gt;', '<span class="highlight-search">', $input);
        $input = str_replace('&lt;' . $tag . '&gt;', '<' . $tag . '>', $input);
        $input = str_replace('&lt;/' . $tag . '&gt;', '</' . $tag . '>', $input);
        $input = str_replace('&lt;' . $tag . '/&gt;', '<' . $tag . '/>', $input);
    }
    return $input;

}

// CUSTOM SQLITE ERROR REPORTING
function get_db_error($dbHandle, $f, $l) {
    $dbError = $dbHandle->errorInfo();
    if (!empty($dbError[1])) {
        die('<div style="padding:10px"><b>SQLite Error:</b> ' . $dbError[2]
                . '<br><b>File:</b> ' . $f
                . '<br><b>Line:</b> ' . $l . '</div>');
    }

}

// NEW PASSWORD FORMAT, SHA512 GRACEFULLY DEGRADES TO CRYPTOGRAPHIC MD5 TO MD5 HASH
function generate_encrypted_password($password) {

    if (defined('CRYPT_SHA512') && CRYPT_SHA512 == 1) {

        if (function_exists('openssl_random_pseudo_bytes')) {

            $bytes = openssl_random_pseudo_bytes(8);
            $salt = bin2hex($bytes);
        } else {

            $salt = substr(md5(mt_rand(0, mt_getrandmax())), mt_rand(0, 16), 16);
        }

        $hash = crypt($password, '$6$rounds=100000$' . $salt . '$');
    } elseif (defined('CRYPT_MD5') && CRYPT_MD5 == 1) {

        if (function_exists('openssl_random_pseudo_bytes')) {

            $bytes = openssl_random_pseudo_bytes(4);
            $salt = bin2hex($bytes);
        } else {

            $salt = substr(md5(mt_rand(0, mt_getrandmax())), mt_rand(0, 24), 8);
        }

        $hash = crypt($password, '$1$' . $salt . '$');
    } else {

        if (function_exists('openssl_random_pseudo_bytes')) {

            $bytes = openssl_random_pseudo_bytes(4);
            $salt = bin2hex($bytes);
        } else {

            $salt = substr(md5(mt_rand(0, mt_getrandmax())), mt_rand(0, 24), 8);
        }

        $hash = '$md5$' . $salt . '$' . md5($salt . $password);
    }

    return $hash;

}

// CHECK PASSWORD (NEW FORMAT)
function check_encrypted_password($dbHandle, $username, $password) {

    $password2 = '';
    $verdict = FALSE;

    // READ PASSWORD FROM DATABASE
    $username_quoted = $dbHandle->quote($username);
    $result = $dbHandle->query("SELECT password FROM users WHERE username=" . $username_quoted);
    $password2 = $result->fetchColumn();
    $result = null;

    if (strlen($password2) == 32) {

        $hash = md5($password);
    } else {

        $dump = array_values(array_filter(explode('$', $password2)));

        // ENCRYPT USERS INPUT WITH CORRECT PROTOCOL AND SALT
        if ($dump[0] == '6') {

            $hash = crypt($password, '$6$' . $dump[1] . '$' . $dump[2] . '$');
        } elseif ($dump[0] == '1') {

            $hash = crypt($password, '$1$' . $dump[1] . '$');
        } elseif ($dump[0] == 'md5') {

            $hash = '$md5$' . $dump[1] . '$' . md5($dump[1] . $password);
        }
    }

    // COMPARE RESULTS, RETURN VERDICT
    if ($hash === $password2)
        $verdict = TRUE;

    // UPGRADE PASSWORD TO NEW FORMAT
    if (strlen($password2) == 32 && $verdict) {
        $newpassword = generate_encrypted_password($password);
        $newpassword = $dbHandle->quote($newpassword);
        $username_quoted = $dbHandle->quote($username);
        $dbHandle->exec("UPDATE users SET password=" . $newpassword . " WHERE username=" . $username_quoted);
    }

    return $verdict;

}

function convert_type($input, $from, $to) {
    $output = 'article';
    if ($input === '')
        $input = 'article';
    $types = array(
        array(
            'ilib' => 'article',
            'bibtex' => 'article',
            'ris' => 'JOUR',
            'endnote' => 'Journal Article',
            'csl' => 'article-journal'
        ),
        array(
            'ilib' => 'book',
            'bibtex' => 'book',
            'ris' => 'BOOK',
            'endnote' => 'Book',
            'csl' => 'book'
        ),
        array(
            'ilib' => 'chapter',
            'bibtex' => 'incollection',
            'ris' => 'CHAP',
            'endnote' => 'Book Section',
            'csl' => 'chapter'
        ),
        array(
            'ilib' => 'conference',
            'bibtex' => 'inproceedings',
            'ris' => 'CONF',
            'endnote' => 'Conference Paper',
            'csl' => 'paper-conference'
        ),
        array(
            'ilib' => 'conference',
            'bibtex' => 'conference',
            'ris' => 'CONF',
            'endnote' => 'Conference Paper',
            'csl' => 'paper-conference'
        ),
        array(
            'ilib' => 'manual',
            'bibtex' => 'manual',
            'ris' => 'STAND',
            'endnote' => 'Standard',
            'csl' => 'article-journal'
        ),
        array(
            'ilib' => 'thesis',
            'bibtex' => 'phdthesis',
            'ris' => 'THES',
            'endnote' => 'Thesis',
            'csl' => 'thesis'
        ),
        array(
            'ilib' => 'thesis',
            'bibtex' => 'mastersthesis',
            'ris' => 'THES',
            'endnote' => 'Thesis',
            'csl' => 'thesis'
        ),
        array(
            'ilib' => 'patent',
            'bibtex' => 'patent',
            'ris' => 'PAT',
            'endnote' => 'Patent',
            'csl' => 'patent'
        ),
        array(
            'ilib' => 'electronic',
            'bibtex' => 'electronic',
            'ris' => 'ELEC',
            'endnote' => 'Electronic Source',
            'csl' => 'webpage'
        ),
        array(
            'ilib' => 'unpublished',
            'bibtex' => 'unpublished',
            'ris' => 'UNPB',
            'endnote' => 'Unpublished Work',
            'csl' => 'manuscript'
        ),
        array(
            'ilib' => 'report',
            'bibtex' => 'techreport',
            'ris' => 'RPRT',
            'endnote' => 'Report',
            'csl' => 'report'
        )
    );
    foreach ($types as $type) {
        if (strtolower($type[$from]) == strtolower($input))
            $output = $type[$to];
    }
    return $output;

}

function cache_name() {

    if (isset($_SESSION['limit']))
        $clipboard[] = $_SESSION['limit'];
    if (isset($_SESSION['orderby']))
        $clipboard[] = $_SESSION['orderby'];
    if (isset($_SESSION['display']))
        $clipboard[] = $_SESSION['display'];
    if (isset($_SESSION['pdfviewer']))
        $clipboard[] = $_SESSION['pdfviewer'];
    $md5_cache_array = array_merge($_POST, $_GET, $clipboard);
    unset($md5_cache_array['_']);
    unset($md5_cache_array['proxystr']);
    ksort($md5_cache_array);
    $md5_cache_string = serialize($md5_cache_array);
    $md5_cache = md5(__FILE__ . $md5_cache_string);
    $cache_name = 'page_' . $md5_cache;
    $cache_name = IL_TEMP_PATH . DIRECTORY_SEPARATOR . 'lib_' . session_id() . DIRECTORY_SEPARATOR . $cache_name;
    return $cache_name;

}

function database_change() {

    $ch_time = 0;
    $ch_time2 = 0;
    $ch_time3 = 0;
    $tables = array();
    $tables2 = array();
    $tables3 = array();
    $tables_arr = func_get_args();
    if (isset($tables_arr[0]))
        $tables = (array) $tables_arr[0];
    if (isset($tables_arr[1]))
        $tables2 = (array) $tables_arr[1];
    if (isset($tables_arr[2]))
        $tables3 = (array) $tables_arr[2];

    // READ DATABASE MTIME

    if (count($tables) > 0) {
        foreach ($tables as $table) {
            $query_arr[] = "ch_table='" . $table . "'";
        }
        $query_str = join(' OR ', $query_arr);

        $dbHandle_t = database_connect(IL_DATABASE_PATH, 'library');
        $result_t = $dbHandle_t->query("SELECT max(ch_time) FROM library_log
            WHERE " . $query_str);
        $ch_time = $result_t->fetchColumn();
        $result_t = null;
        $dbHandle_t = null;
    }

    if (count($tables2) > 0) {
        foreach ($tables2 as $table) {
            $query_arr[] = "ch_table='" . $table . "'";
        }
        $query_str = join(' OR ', $query_arr);

        $dbHandle_t = database_connect(IL_DATABASE_PATH, 'fulltext');
        $result_t = $dbHandle_t->query("SELECT max(ch_time) FROM fulltext_log
            WHERE " . $query_str);
        $ch_time2 = $result_t->fetchColumn();
        $result_t = null;
        $dbHandle_t = null;
    }

    if (count($tables3) > 0) {
        $dbHandle_t = database_connect(IL_DATABASE_PATH, 'library');
        attach_clipboard($dbHandle_t);
        $result_t = $dbHandle_t->query("SELECT ch_time FROM clipboard.clipboard_log");
        $ch_time3 = $result_t->fetchColumn();
        $result_t = null;
        $dbHandle_t = null;
    }

    return max($ch_time, $ch_time2, $ch_time3);

}

function cache_start($ch_time) {

    global $cache_name;
    $mtime = 0;

    // READ CACHE MTIME

    if (is_file($cache_name))
        $mtime = filemtime($cache_name);

    // EITHER SHOW CACHED PAGE OR CONTINUE

    if ($ch_time < $mtime) {
        if (file_exists($cache_name)) {
            $cached_string = file_get_contents($cache_name);
            echo $cached_string;
            exit();
        }
    }
    ob_start();

}

function cache_store() {

    global $cache_name;

    // GET BUFFER CONTENTS

    $bufferContent = ob_get_contents();
    ob_end_flush();

    // STORE BUFFER INTO CACHE

    file_put_contents($cache_name, $bufferContent);

}

function graphical_abstract($file) {
    $filename = sprintf("%05d", intval($file));
    $filename_array = glob(IL_SUPPLEMENT_PATH . DIRECTORY_SEPARATOR . get_subfolder($filename) . DIRECTORY_SEPARATOR . $filename . 'graphical_abstract.*');
    if (!empty($filename_array[0]))
        return $filename_array[0];

}

function get_username($dbHandle, $userID) {
    $path_q = $dbHandle->quote(IL_USER_DATABASE_PATH . DIRECTORY_SEPARATOR . 'users.sq3');
    $dbHandle->exec("ATTACH DATABASE " . $path_q . " AS usersdatabase");
    $query = $dbHandle->quote($userID);
    $result = $dbHandle->query("SELECT usersdatabase.users.username AS username FROM usersdatabase.users WHERE userID = $query LIMIT 1");
    $username = $result->fetchColumn();
    $dbHandle->exec("DETACH DATABASE usersdatabase");
    return $username;

}

/////////////create, upgrade, or connect to database//////////////////////

function database_connect($database_path, $database_name) {

    global $dbHandle;

    /////////////connect to database//////////////////////
    try {
        $dbHandle = new PDO('sqlite:' . $database_path . DIRECTORY_SEPARATOR . $database_name . '.sq3');
    } catch (PDOException $e) {
        print "Error: " . $e->getMessage() . "<br/>";
        print "PHP extensions PDO and PDO_SQLite must be installed.";
        die();
    }

    $dbHandle->sqliteCreateFunction('search_strip_tags', 'sqlite_strip_tags', 1);

    return $dbHandle;
}

/////////////sqlite_regexp//////////////////////

function sqlite_regexp($string1, $string2, $case) {

    if ($case == 1) {
        $pattern = '/([^a-zA-Z0-9]|^)' . $string2 . '([^a-zA-Z0-9]|$)/u';
    } else {
        $pattern = '/([^a-zA-Z0-9]|^)' . $string2 . '([^a-zA-Z0-9]|$)/ui';
    }

    if (preg_match($pattern, $string1) > 0) {
        return true;
    } else {
        return false;
    }

}

/////////////sqlite_strip_tags//////////////////////

function sqlite_strip_tags($string) {

    return html_entity_decode(strip_tags($string), ENT_QUOTES, 'UTF-8');

}

/////////////sqlite_levenshtein//////////////////////

function sqlite_levenshtein($string1, $string2) {

    $replacements = array('.', '&', 'and');
    $string1 = str_ireplace($replacements, '', $string1);
    $string2 = str_ireplace($replacements, '', $string2);
    if (stripos($string1, 'the ') === 0)
        $string1 = substr($string1, 4);
    if (stripos($string2, 'the ') === 0)
        $string2 = substr($string2, 4);
    return levenshtein($string1, $string2);

}

/////////////select pdftotext//////////////////////

function select_pdftotext() {

    $path = __DIR__ . DIRECTORY_SEPARATOR . 'bin' . DIRECTORY_SEPARATOR . 'poppler';
    $output = 'pdftotext';

    if (strtoupper(substr(PHP_OS, 0, 3)) == 'WIN' && is_executable($path . DIRECTORY_SEPARATOR . $output . '.exe')) {
        $output = $path . DIRECTORY_SEPARATOR . $output . '.exe';
    } elseif (is_executable($path . DIRECTORY_SEPARATOR . $output)) {
        $output = $path . DIRECTORY_SEPARATOR . $output;
    }

    return '"' . $output . '"';

}

/////////////select pdfinfo//////////////////////

function select_pdfinfo() {

    $path = __DIR__ . DIRECTORY_SEPARATOR . 'bin' . DIRECTORY_SEPARATOR . 'poppler';
    $output = 'pdfinfo';

    if (strtoupper(substr(PHP_OS, 0, 3)) == 'WIN' && is_executable($path . DIRECTORY_SEPARATOR . $output . '.exe')) {
        $output = $path . DIRECTORY_SEPARATOR . $output . '.exe';
    } elseif (PHP_OS == 'Darwin' && is_executable($path . DIRECTORY_SEPARATOR . 'Frameworks' . DIRECTORY_SEPARATOR . $output)) {
        $output = $path . DIRECTORY_SEPARATOR . 'Frameworks' . DIRECTORY_SEPARATOR . $output;
    }

    return '"' . $output . '"';

}

/////////////select pdftohtml//////////////////////

function select_pdftohtml() {

    $path = __DIR__ . DIRECTORY_SEPARATOR . 'bin' . DIRECTORY_SEPARATOR . 'poppler';
    $output = 'pdftohtml';

    if (strtoupper(substr(PHP_OS, 0, 3)) == 'WIN' && is_executable($path . DIRECTORY_SEPARATOR . $output . '.exe')) {
        $output = $path . DIRECTORY_SEPARATOR . $output . '.exe';
    } elseif (PHP_OS == 'Darwin' && is_executable($path . DIRECTORY_SEPARATOR . 'Frameworks' . DIRECTORY_SEPARATOR . $output)) {
        $output = $path . DIRECTORY_SEPARATOR . 'Frameworks' . DIRECTORY_SEPARATOR . $output;
    }

    return '"' . $output . '"';

}

/////////////select pdfdetach//////////////////////

function select_pdfdetach() {

    $path = __DIR__ . DIRECTORY_SEPARATOR . 'bin' . DIRECTORY_SEPARATOR . 'poppler';
    $output = 'pdfdetach';

    if (strtoupper(substr(PHP_OS, 0, 3)) == 'WIN' && is_executable($path . DIRECTORY_SEPARATOR . $output . '.exe')) {
        $output = $path . DIRECTORY_SEPARATOR . $output . '.exe';
    } elseif (is_executable($path . DIRECTORY_SEPARATOR . $output)) {
        $output = $path . DIRECTORY_SEPARATOR . $output;
    }

    return '"' . $output . '"';

}

/////////////select ghostscript//////////////////////

function select_ghostscript() {

    $path = __DIR__ . DIRECTORY_SEPARATOR . 'bin' . DIRECTORY_SEPARATOR . 'gs';
    $output = 'gs';

    if (strtoupper(substr(PHP_OS, 0, 3)) == 'WIN' && is_executable($path . DIRECTORY_SEPARATOR . $output . 'win32c.exe')) {
        $output = $path . DIRECTORY_SEPARATOR . $output . 'win32c.exe';
    } elseif (is_executable($path . DIRECTORY_SEPARATOR . $output)) {
        $output = $path . DIRECTORY_SEPARATOR . $output;
    }

    return '"' . $output . '"';

}

/////////////select tesseract//////////////////////

function select_tesseract() {

    $output = 'tesseract';

    if (strtoupper(substr(PHP_OS, 0, 3)) == 'WIN') {
        $output = '%PROGRAMFILES%\\Tesseract-OCR\\tesseract.exe';
    }

    return '"' . $output . '"';

}

/////////////select soffice//////////////////////

function select_soffice() {

    $output = 'soffice';

    if (strtoupper(substr(PHP_OS, 0, 3)) == 'WIN') {
        if (!empty($_SESSION['soffice_path'])) {
            $output = $_SESSION['soffice_path'] . DIRECTORY_SEPARATOR . 'soffice.exe';
        } else {
            $output = '%PROGRAMFILES%\\LibreOffice 4\\program\\soffice.exe';
        }
    }

    return '"' . $output . '"';

}

/**
 *
 * Check if URL is safe.
 *
 * @param  string $url
 * @return boolean
 *
 */
function isSafeUrl($url) {

    $url = trim($url);

    $host = parse_url($url, PHP_URL_HOST);

    // Host required.
    if ($host === null) {
        return false;
    }

    // Prevent IP based addresses and localhost.
    if ($host === 'localhost'
        || !filter_var($url, FILTER_VALIDATE_URL, FILTER_FLAG_HOST_REQUIRED)
        || filter_var($host, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4)
        || filter_var($host, FILTER_VALIDATE_IP, FILTER_FLAG_IPV6)) {

        return false;
    }

    // Deal with wildcard DNS.
    $ip = gethostbyname($host);
    $ip = ip2long($ip);

    if($ip === false){
        return false;
    }

    $is_inner_ipaddress =
        ip2long('127.0.0.0')   >> 24 === $ip >> 24 or
        ip2long('10.0.0.0')    >> 24 === $ip >> 24 or
        ip2long('172.16.0.0')  >> 20 === $ip >> 20 or
        ip2long('169.254.0.0') >> 16 === $ip >> 16 or
        ip2long('192.168.0.0') >> 16 === $ip >> 16;

    if($is_inner_ipaddress){
        return false;
    }

    return true;
}

/**
 *
 * GET request to fetch resources from the web.
 *
 * @param string $url
 * @param string $proxy_name
 * @param string $proxy_port
 * @param string $proxy_username
 * @param string $proxy_password
 * @return string
 *
 */
function getFromWeb($url, $proxy_name, $proxy_port, $proxy_username, $proxy_password, $referer = '', $number = 0) {

    // Check URL.
    if (isSafeUrl($url) === false) {
        return '';
    }

    // Max number of redirects.
    if ($number > 5) {
        return '';
    }

    $curl = curl_init();

    if (!empty($proxy_name) && !empty($proxy_port)) {

        // enable NTLM proxy authentication
        // curl_setopt($curl, CURLOPT_PROXYAUTH, CURLAUTH_NTLM);
        curl_setopt($curl, CURLOPT_PROXY, "$proxy_name:$proxy_port");

        if (!empty($proxy_username) && !empty($proxy_password)) {

            curl_setopt($curl, CURLOPT_PROXYUSERPWD, "$proxy_username:$proxy_password");
        }
    }

    curl_setopt($curl, CURLOPT_URL, $url);
    curl_setopt($curl, CURLOPT_USERAGENT,"$_SERVER[HTTP_USER_AGENT]");
    curl_setopt($curl, CURLOPT_CONNECTTIMEOUT, 30);
    curl_setopt($curl, CURLOPT_TIMEOUT, 30);
    curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt($curl, CURLOPT_HEADER, 1);

    if ($referer != '') {
        curl_setopt($curl, CURLOPT_REFERER, $referer);
    }

    // Do not verify TLS certificates on OSes where we don't have the certificate bundle.
    if (PHP_OS !== 'Linux') {

        curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, 0);
        curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, 0);
    }

    $response = curl_exec($curl);

    curl_close($curl);

    // Curl error.
    if ($response === false) {

        return '';
    }

    // Split response to headers and body.
    $response_parts = explode("\r\n\r\n", $response);

    // Redirect if Location found.
    if (strpos($response_parts[0], 'Location:') !== false) {

        // Extract location.
        preg_match('/(Location:)(.*)/', $response_parts[0], $matches);
        $location = trim($matches[2]);

        // Check if Location is a relative URL.
        if (parse_url($location, PHP_URL_HOST) === null) {

            // Combine new relative URL ($location) with the previous URL ($url).

            // Is there a user:password?
            $user_pass = '';
            $url_user = parse_url($url, PHP_URL_USER);
            $url_pass = parse_url($url, PHP_URL_PASS);

            if ($url_user !== null && $url_pass !== null) {

                $user_pass = "$url_user:$url_pass@";
            }

            // Is there a port?
            $port = parse_url($url, PHP_URL_PORT) === null ? '' : ':' . parse_url($url, PHP_URL_PORT);

            // Assemble new URL.
            $new_url = parse_url($url, PHP_URL_SCHEME) . '://'
                . $user_pass
                . parse_url($url, PHP_URL_HOST)
                . $port
                . $location;

        } else {

            // Check if Location is a protocol-relative link.
            if (parse_url($location, PHP_URL_SCHEME) === null) {

                // Add scheme from the previous URL ($url).
                $new_url = parse_url($url, PHP_URL_SCHEME) . ":$location";

            } else {

                // New URL is a fully qualified URL.
                $new_url = $location;
            }
        }

        return getFromWeb($new_url, $proxy_name, $proxy_port, $proxy_username, $proxy_password, $url, $number + 1);
    }

    // Return response body.
    return $response_parts[1];
}

/////////////proxy_simplexml_load_file//////////////////////

function proxy_simplexml_load_file($url, $proxy_name, $proxy_port, $proxy_username, $proxy_password) {

    $xml = false;
    $xml_string = '';

    $xml_string = getFromWeb($url, $proxy_name, $proxy_port, $proxy_username, $proxy_password);

    $xml = simplexml_load_string($xml_string);

    return $xml;
}

//FETCH METADATA FROM NASA ADS
function fetch_from_nasaads($doi, $nasa_id) {

    global $proxy_name, $proxy_port, $proxy_username, $proxy_password, $response;

    if (!empty($nasa_id))
        $lookfor = 'bibcode=' . urlencode($nasa_id);
    if (!empty($doi))
        $lookfor = 'doi=' . urlencode($doi);

    $request_url = "http://adsabs.harvard.edu/cgi-bin/abs_connect?" . $lookfor . "&data_type=XML&return_req=no_params&start_nr=1&nr_to_return=1";

    $xml = proxy_simplexml_load_file($request_url, $proxy_name, $proxy_port, $proxy_username, $proxy_password);
    if (empty($xml))
        die('Error! I, Librarian could not connect with an external web service. This usually indicates that you access the Web through a proxy server.
            Enter your proxy details in Tools->Settings. Alternatively, the external service may be temporarily down. Try again later.');

    foreach ($xml->attributes() as $a => $b) {

        if ($a == 'selected') {
            $count = $b;
            break;
        }
    }

    if ($count == 1) {

        $record = $xml->record;

        $bibcode = (string) $record->bibcode;
        $response['title'] = (string) $record->title;

        $journal = (string) $record->journal;
        if (strstr($journal, ","))
            $response['secondary_title'] = substr($journal, 0, strpos($journal, ','));

        $eprintid = $record->eprintid;
        if (!empty($eprintid))
            $eprintid = substr($eprintid, strpos($eprintid, ":") + 1);
        if (strstr($journal, "arXiv"))
            $eprintid = substr($journal, strpos($journal, ":") + 1);

        $doi = $record->DOI;
        if (!empty($doi))
            $response['doi'] = $doi;

        $response['volume'] = (string) $record->volume;
        $response['pages'] = (string) $record->page;
        $last_page = (string) $record->lastpage;
        if (!empty($last_page))
            $response['pages'] = $response['pages'] . '-' . $last_page;

        $response['affiliation'] = (string) $record->affiliation;

        $year = (string) $record->pubdate;
        $response['year'] = date('Y-m-d', strtotime($year));

        $response['abstract'] = (string) $record->abstract;
        if ($response['abstract'] == 'Not Available')
            unset($response['abstract']);
        $nasa_url = (string) $record->url;

        foreach ($record->link as $links) {

            foreach ($links->attributes() as $a => $b) {

                if ($a == 'type' && $b == 'EJOURNAL') {
                    $ejournal_url = (string) $links->url;
                } elseif ($a == 'type' && $b == 'PREPRINT') {
                    $preprint_url = (string) $links->url;
                } elseif ($a == 'type' && $b == 'GIF') {
                    $gif_url = (string) $links->url;
                }
            }
        }

        $authors = $record->author;

        $name_array = array();

        if (!empty($authors)) {

            foreach ($authors as $author) {
                $author_array = explode(",", $author);
                $name_array[] = 'L:"' . trim($author_array[0]) . '",F:"' . trim($author_array[1]) . '"';
                $response['last_name'][] = trim($author_array[0]);
                $response['first_name'][] = trim($author_array[1]);
            }
        }

        if (isset($name_array))
            $response['authors'] = join(";", $name_array);

        $keywords = $record->keywords;

        if (!empty($keywords)) {

            foreach ($keywords as $keyword) {

                $keywords_array[] = (string) $keyword->keyword;
            }
        }

        if (isset($keywords_array))
            $response['keywords'] = join(" / ", $keywords_array);

        $response['uid'] = array();
        if (!empty($bibcode))
            $response['uid'][] = "NASAADS:$bibcode";
        if (!empty($eprintid))
            $response['uid'][] = "ARXIV:$eprintid";

        $response['url'] = array();
        $response['url'][] = $nasa_url;
        if (!empty($eprintid))
            $response['url'][] = "http://arxiv.org/abs/$eprintid";
    }

}

//FETCH METADATA FROM CROSSREF
function fetch_from_crossref($doi) {

    global $proxy_name, $proxy_port, $proxy_username, $proxy_password, $response;

    $request_url = "http://www.crossref.org/openurl/?id=doi:" . urlencode($doi) . "&noredirect=true&pid=i.librarian.software@gmail.com";

    $xml = proxy_simplexml_load_file($request_url, $proxy_name, $proxy_port, $proxy_username, $proxy_password);
    if (empty($xml))
        die('Error! I, Librarian could not connect with Crossref. This usually indicates that you access the Web through a proxy server.
                Enter your proxy details in Tools->Settings. Alternatively, the external service may be temporarily down. Try again later.');

    $resolved = false;

    $record = $xml->query_result->body->query;

    foreach ($record->attributes() as $a => $b) {

        if ($a == 'status' && $b == 'resolved') {
            $resolved = true;
            break;
        }
    }

    if ($resolved) {

        $response['doi'] = $doi;

        $title = html_entity_decode($record->article_title);
        $journal_title = html_entity_decode($record->journal_title);
        $volume_title = html_entity_decode($record->volume_title);
        $series_title = html_entity_decode($record->series_title);

        if (!empty($title)) {
            $response['title'] = $title;
        } elseif (!empty($volume_title)) {
            $response['title'] = $volume_title;
            $response['reference_type'] = 'book';
        }

        if (!empty($journal_title)) {
            $response['secondary_title'] = $journal_title;
            $response['reference_type'] = 'article';
        }

        if (!empty($volume_title) && !empty($title)) {
            $response['secondary_title'] = $volume_title;
            if (!empty($series_title))
                $response['tertiary_title'] = $series_title;
            $response['reference_type'] = 'chapter';
        }

        if (!empty($volume_title) && empty($title)) {
            if (!empty($series_title))
                $response['secondary_title'] = $series_title;
            $response['reference_type'] = 'book';
        }

        $year = html_entity_decode($record->year);
        if (!empty($year))
            $response['year'] = $year . '-01-01';

        $volume = html_entity_decode($record->volume);
        if (!empty($volume))
            $response['volume'] = $volume;

        $issue = html_entity_decode($record->issue);
        if (!empty($issue))
            $response['issue'] = $issue;

        $pages = html_entity_decode($record->first_page);
        if (!empty($pages))
            $response['pages'] = $pages;

        $last_page = html_entity_decode($record->last_page);

        if (!empty($last_page))
            $response['pages'] = $response['pages'] . "-" . $last_page;

        $authors = array();
        $editors = array();
        $contributors = $record->contributors->contributor;
        if (count($contributors) > 0) {
            foreach ($contributors as $contributor) {

                foreach ($contributor->attributes() as $a => $b) {

                    if ($a == 'contributor_role' && $b == 'author') {
                        $authors1 = html_entity_decode($contributor->surname);
                        $authors2 = html_entity_decode($contributor->given_name);
                        $authors[] = 'L:"' . $authors1 . '",F:"' . $authors2 . '"';
                        $response['last_name'][] = $authors1;
                        $response['first_name'][] = $authors2;
                    } elseif ($a == 'contributor_role' && $b == 'editor') {
                        $authors1 = html_entity_decode($contributor->surname);
                        $authors2 = html_entity_decode($contributor->given_name);
                        $editors[] = 'L:"' . $authors1 . '",F:"' . $authors2 . '"';
                    }
                }
            }
        }
        if (count($authors) > 0)
            $response['authors'] = join(";", $authors);
        if (count($editors) > 0)
            $response['editor'] = join(";", $editors);
    }

}

//FETCH METADATA FROM GOOGLE PATENTS
function fetch_from_googlepatents($patent_id) {

    global $proxy_name, $proxy_port, $proxy_username, $proxy_password, $response;

    $request_url = "http://www.google.com/patents/" . urlencode($patent_id);

    $dom = getFromWeb($request_url, $proxy_name, $proxy_port, $proxy_username, $proxy_password);

    if (empty($dom))
        die('Error! I, Librarian could not connect with Google Patents. This usually indicates that you access the Web through a proxy server.
            Enter your proxy details in Tools->Settings. Alternatively, Google Patents may be temporarily down. Try again later.');

    preg_match_all('/(\<meta name=\"DC\.contributor\" content=\")(.+)(\")/Ui', $dom, $authors);

    $response['affiliation'] = array_pop($authors[2]);

    //GET AUTHORS AND ASSIGNEE
    $name_array = array();
    if (!empty($authors[2])) {

        foreach ($authors[2] as $author) {

            $author_array = explode(' ', $author);
            $last = array_pop($author_array);
            $first = join(' ', $author_array);
            $name_array[] = 'L:"' . $last . '",F:"' . $first . '"';
            $response['last_name'][] = $last;
            $response['first_name'][] = $first;
        }
    }

    if (isset($name_array)) {
        $response['authors'] = join(";", $name_array);
    }

    //GET PDF LINK
    preg_match('/(\<a id=\"appbar\-download\-pdf\-link\" href=\")(.+)(\">\<\/a\>)/Ui', $dom, $pdf_link);
    if (!empty($pdf_link[2])) {
        $response['form_new_file_link'] = 'http:' . htmlspecialchars($pdf_link[2]);
    }

    //GET OTHER META TAGS
    file_put_contents(IL_TEMP_PATH . DIRECTORY_SEPARATOR . 'patent' . urlencode($patent_id), $dom);
    $tags = get_meta_tags(IL_TEMP_PATH . DIRECTORY_SEPARATOR . 'patent' . urlencode($patent_id));
    $response['title'] = $tags['dc_title'];
    $response['abstract'] = $tags['dc_description'];
    $response['year'] = $tags['dc_date'];
    unlink(IL_TEMP_PATH . DIRECTORY_SEPARATOR . 'patent' . urlencode($patent_id));

}

//FETCH METADATA FROM OPEN LIBRARY
function fetch_from_ol($ol_id, $isbn) {

    global $proxy_name, $proxy_port, $proxy_username, $proxy_password, $response;

    if (!empty($ol_id)) {
        $query = 'q=' . urlencode($ol_id);
    } elseif (!empty($isbn)) {
        $query = 'isbn=' . urlencode($isbn);
    }

    $request_url = "http://openlibrary.org/search.json?" . $query;

    $ol = getFromWeb($request_url, $proxy_name, $proxy_port, $proxy_username, $proxy_password);

    if (empty($ol))
        die('Error! I, Librarian could not connect with Open Library. This usually indicates that you access the Web through a proxy server.
            Enter your proxy details in Tools->Settings. Alternatively,  Open Library may be temporarily down. Try again later.');

    $ol = json_decode($ol, true);

    if ($ol['num_found'] !== 1)
        die('Error! Unique record not found in Open Library.');

    $record = $ol['docs'][0];
    $response['title'] = $record['title'];
    if (isset($record['subtitle']))
        $response['title'] .= ': ' . $record['subtitle'];
    $response['year'] = $record['first_publish_year'] . '-01-01';

    //GET AUTHORS
    $name_array = array();
    if (count($record['author_name']) > 0) {

        foreach ($record['author_name'] as $author) {

            $author_array = explode(' ', $author);
            $last = array_pop($author_array);
            $first = join(' ', $author_array);
            $name_array[] = 'L:"' . $last . '",F:"' . $first . '"';
            $response['last_name'][] = $last;
            $response['first_name'][] = $first;
        }
    }
    if (isset($name_array))
        $response['authors'] = join(";", $name_array);

    $response['keywords'] = '';

    if (count($record['subject']) > 0) {
        $response['keywords'] = join(' / ', $record['subject']);
    }

    $response['reference_type'] = 'book';

    if (empty($ol_id)) {
        $ol_id = $record['edition_key'][0];
        $response['uid'][] = 'OL:' . $ol_id;
    }
    if (empty($isbn)) {
        $isbn = $record['isbn'][0];
        $response['uid'][] = 'ISBN:' . $isbn;
    }

    $response['url'][] = 'http://openlibrary.org/book/' . $ol_id;

}

//FETCH METADATA FROM IEEE XPLORE
function fetch_from_ieee($doi, $ieee_id) {

    // Remove IEEE search.
    return '';

    if (!empty($doi)) {
        $query = "doi=" . urlencode($doi);
    } elseif (!empty($ieee_id)) {
        $query = "an=" . urlencode($ieee_id);
    }

    global $proxy_name, $proxy_port, $proxy_username, $proxy_password, $response;

    $request_url = "http://ieeexplore.ieee.org/gateway/ipsSearch.jsp?" . $query;

    $xml = proxy_simplexml_load_file($request_url, $proxy_name, $proxy_port, $proxy_username, $proxy_password);

    if ($xml === FALSE) {
        die('Error! I, Librarian could not connect with an external web service. This usually indicates that you access the Web through a proxy server.
            Enter your proxy details in Tools->Settings. Alternatively, the external service may be temporarily down. Try again later.');
    }

    $count = 0;
    $count = (string) $xml->totalfound;

    if ($count > 0) {

        $item = $xml->document;

        // TITLE
        $response['title'] = (string) $item->title;

        // IEEE ID
        $id = (string) $item->arnumber;
        $response['uid'][] = 'IEEE:' . $id;

        // AUTHORS
        $authors = (string) $item->authors;
        $author_array = explode(";", $authors);
        foreach ($author_array as $author) {

            $author = trim($author);
            $comma = strpos($author, ",");
            $space = strpos($author, " ");

            if ($comma === FALSE) {

                $response['first_name'][] = trim(substr($author, 0, $space));
                $response['last_name'][] = trim(substr($author, $space + 1));
                $name_array[] = 'L:"' . trim(substr($author, $space + 1)) . '",F:"' . trim(substr($author, 0, $space)) . '"';
            } else {

                $response['last_name'][] = trim(substr($author, 0, $comma));
                $response['first_name'][] = trim(substr($author, $comma + 1));
                $name_array[] = 'L:"' . trim(substr($author, 0, $comma)) . '",F:"' . trim(substr($author, $comma + 1)) . '"';
            }
        }

        if (isset($name_array)) {
            $response['authors'] = join(";", $name_array);
        }

        // Affiliation.
        $response['affiliation'] = (string) $item->affiliations;

        // DOI
        $response['doi'] = (string) $item->doi;

        // YEAR
        $response['year'] = (string) $item->py;

        // Secondary title.
        $response['secondary_title'] = (string) $item->pubtitle;

        // Volume.
        $response['volume'] = (string) $item->volume;

        // Issue.
        $response['issue'] = (string) $item->issue;

        // Pages.
        $response['pages'] = (string) $item->spage;
        $epage = (string) $item->epage;
        if (!empty($epage) && $epage != $response['pages']) {
            $response['pages'] .= '-' . $epage;
        }

        // Keywords.
        $keywords_array = array();
        if (count($item->controlledterms->term) > 0) {

            foreach ($item->controlledterms->term as $keyword) {

                $keywords_array[] = (string) $keyword;
            }

            if (!empty($keywords_array)) {
                $response['keywords'] = join(" / ", $keywords_array);
            }
        }

        // Publisher.
        $response['publisher'] = (string) $item->publisher;

        // Abstract.
        $response['abstract'] = (string) $item->abstract;

        // Reference type.
        $reference_type = (string) $item->pubtype;

        if ($reference_type == 'Conference Publications') {

            $response['reference_type'] = 'conference';
        } elseif ($reference_type == 'Journals & Magazines') {

            $response['reference_type'] = 'article';
        } elseif ($reference_type == 'Books & eBooks') {

            $response['reference_type'] = 'chapter';
        } elseif ($reference_type == 'Early Access Articles') {

            $response['reference_type'] = 'article';
        } elseif ($reference_type == 'Standards') {

            $response['reference_type'] = 'manual';
        }
    }
}

//FETCH METADATA FROM ARXIV
function fetch_from_arxiv($arxiv_id) {

    global $proxy_name, $proxy_port, $proxy_username, $proxy_password, $response;

    $request_url = "http://export.arxiv.org/api/query?id_list=" . urlencode($arxiv_id) . "&start=0&max_results=1";

    $xml = proxy_simplexml_load_file($request_url, $proxy_name, $proxy_port, $proxy_username, $proxy_password);
    if (empty($xml))
        die('Error! I, Librarian could not connect with an external web service. This usually indicates that you access the Web through a proxy server.
            Enter your proxy details in Tools->Settings. Alternatively, the external service may be temporarily down. Try again later.');

    $record = $xml->entry;

    $response['title'] = (string) $record->title;
    ;
    if ($response['title'] != 'Error') {

        $children = $record->children('http://arxiv.org/schemas/atom');
        $response['secondary_title'] = (string) $children->journal_ref;

        $response['doi'] = (string) $children->doi;

        $pub_date = (string) $record->published;
        $response['year'] = date("Y-m-d", strtotime($pub_date));

        $response['abstract'] = trim((string) $record->summary);

        $authors = $record->author;

        $name_array = array();
        if (!empty($authors)) {

            foreach ($authors as $author) {

                $author = (string) $author->name;
                $author_array = explode(' ', $author);
                $last = array_pop($author_array);
                $first = join(' ', $author_array);
                $response['last_name'][] = $last;
                $response['first_name'][] = $first;
                $name_array[] = 'L:"' . $last . '",F:"' . $first . '"';
            }
        }

        if (isset($name_array))
            $response['authors'] = join(";", $name_array);

        $category = $children->primary_category;
        $response['keywords'] = $category->attributes();

        $response['uid'][] = "ARXIV:$arxiv_id";

        $response['url'][] = "http://arxiv.org/abs/$arxiv_id";
    }

}

//FETCH METADATA FROM PUBMED
function fetch_from_pubmed($doi, $pmid) {

    global $proxy_name, $proxy_port, $proxy_username, $proxy_password, $response;

    if (empty($pmid) && !empty($doi)) {

        $request_url = "https://www.ncbi.nlm.nih.gov/entrez/eutils/esearch.fcgi?db=Pubmed&term=" . $doi . "[AID]&usehistory=y&retstart=&retmax=1&sort=&tool=I,Librarian&email=i.librarian.software@gmail.com";

        $xml = proxy_simplexml_load_file($request_url, $proxy_name, $proxy_port, $proxy_username, $proxy_password);
        if (empty($xml))
            die('Error! I, Librarian could not connect with an external web service. This usually indicates that you access the Web through a proxy server.
            Enter your proxy details in Tools->Settings. Alternatively, the external service may be temporarily down. Try again later.');

        $count = $xml->Count;
        if ($count == 1)
            $pmid = (string) $xml->IdList->Id;
    }

    if (!empty($pmid)) {

        ##########	open efetch, read xml	##########

        $request_url = "https://www.ncbi.nlm.nih.gov/entrez/eutils/efetch.fcgi?db=Pubmed&rettype=abstract&retmode=XML&id=" . urlencode($pmid) . "&tool=I,Librarian&email=i.librarian.software@gmail.com";

        $xml = proxy_simplexml_load_file($request_url, $proxy_name, $proxy_port, $proxy_username, $proxy_password);
        if (empty($xml))
            die('Error! I, Librarian could not connect with an external web service. This usually indicates that you access the Web through a proxy server.
            Enter your proxy details in Tools->Settings. Alternatively, the external service may be temporarily down. Try again later.');

        $xml_type = '';
        if (!empty($xml->PubmedBookArticle))
            $xml_type = 'book';
        if (!empty($xml->PubmedArticle))
            $xml_type = 'article';

        if ($xml_type == 'article') {

            foreach ($xml->PubmedArticle->PubmedData->ArticleIdList->ArticleId as $articleid) {

                foreach ($articleid->attributes() as $a => $b) {

                    if ($a == 'IdType' && $b == 'doi')
                        $doi = $articleid[0];
                    if ($a == 'IdType' && $b == 'pmc') {
                        $response['pmcid'] = substr($articleid[0], 3);
                        $response['uid'][] = 'PMCID:' . $response['pmcid'];
                    }
                }
            }

            if (!empty($doi))
                $response['doi'] = $doi;

            $response['uid'][] = 'PMID:' . $pmid;

            $response['url'][] = "https://www.ncbi.nlm.nih.gov/pubmed/$pmid";

            $response['reference_type'] = 'article';

            $response['title'] = (string) $xml->PubmedArticle->MedlineCitation->Article->ArticleTitle;

            $abstract_array = array();

            $xml_abstract = $xml->PubmedArticle->MedlineCitation->Article->Abstract->AbstractText;

            if (!empty($xml_abstract)) {
                foreach ($xml_abstract as $mini_ab) {
                    foreach ($mini_ab->attributes() as $a => $b) {
                        if ($a == 'Label')
                            $mini_ab = $b . ": " . $mini_ab;
                    }
                    $abstract_array[] = "$mini_ab";
                }
                $response['abstract'] = implode(' ', $abstract_array);
            }

            $response['secondary_title'] = (string) $xml->PubmedArticle->MedlineCitation->Article->Journal->Title;

            $day = (string) $xml->PubmedArticle->MedlineCitation->Article->Journal->JournalIssue->PubDate->Day;
            $month = (string) $xml->PubmedArticle->MedlineCitation->Article->Journal->JournalIssue->PubDate->Month;
            $year = (string) $xml->PubmedArticle->MedlineCitation->Article->Journal->JournalIssue->PubDate->Year;

            if (empty($year)) {
                $year = (string) $xml->PubmedArticle->MedlineCitation->Article->Journal->JournalIssue->PubDate->MedlineDate;
                preg_match('/\d{4}/', $year, $year_match);
                $year = $year_match[0];
            }

            $response['year'] = '';
            if (!empty($year)) {
                if (empty($day))
                    $day = '01';
                if (empty($month))
                    $month = '01';
                $response['year'] = date('Y-m-d', strtotime($day . '-' . $month . '-' . $year));
            }

            $response['volume'] = (string) $xml->PubmedArticle->MedlineCitation->Article->Journal->JournalIssue->Volume;

            $response['issue'] = (string) $xml->PubmedArticle->MedlineCitation->Article->Journal->JournalIssue->Issue;

            $response['pages'] = (string) $xml->PubmedArticle->MedlineCitation->Article->Pagination->MedlinePgn;

            $response['journal_abbr'] = (string) $xml->PubmedArticle->MedlineCitation->MedlineJournalInfo->MedlineTA;

            $authors = $xml->PubmedArticle->MedlineCitation->Article->AuthorList->Author;

            $name_array = array();
            $response['affiliation'] = '';
            if (!empty($authors)) {
                foreach ($authors as $author) {
                    $name_array[] = 'L:"' . (string) $author->LastName . '",F:"' . (string) $author->ForeName . '"';
                    $response['last_name'][] = (string) $author->LastName;
                    $response['first_name'][] = (string) $author->ForeName;
                    if (empty($response['affiliation'])) {
                        $response['affiliation'] = (string) $author->AffiliationInfo->Affiliation;
                    }
                }
            }

            $mesh = $xml->PubmedArticle->MedlineCitation->MeshHeadingList->MeshHeading;

            if (!empty($mesh)) {
                foreach ($mesh as $meshheading) {
                    $mesh_array[] = (string) $meshheading->DescriptorName;
                }
            }

            if (isset($name_array))
                $response['authors'] = join(";", $name_array);
            if (isset($mesh_array))
                $response['keywords'] = join(" / ", $mesh_array);
        }

        if ($xml_type == 'book') {

            $pmid = (string) $xml->PubmedBookArticle->BookDocument->PMID;

            $response['uid'][] = "PMID:$pmid";

            $response['url'][] = "https://www.ncbi.nlm.nih.gov/pubmed/$pmid";

            $response['title'] = (string) $xml->PubmedBookArticle->BookDocument->ArticleTitle;

            if (empty($response['title'])) {
                $response['reference_type'] = 'book';
                $response['title'] = (string) $xml->PubmedBookArticle->BookDocument->Book->BookTitle;
                $response['secondary_title'] = (string) $xml->PubmedBookArticle->BookDocument->Book->CollectionTitle;
            } else {
                $response['reference_type'] = 'chapter';
                $response['secondary_title'] = (string) $xml->PubmedBookArticle->BookDocument->Book->BookTitle;
                $response['tertiary_title'] = (string) $xml->PubmedBookArticle->BookDocument->Book->CollectionTitle;
            }

            $response['publisher'] = (string) $xml->PubmedBookArticle->BookDocument->Book->Publisher->PublisherName;
            $response['place_published'] = (string) $xml->PubmedBookArticle->BookDocument->Book->Publisher->PublisherLocation;

            $abstract_array = array();

            foreach ($xml->PubmedBookArticle->BookDocument->Abstract->AbstractText as $mini_ab) {

                foreach ($mini_ab->attributes() as $a => $b) {
                    if ($a == 'Label')
                        $mini_ab = $b . ": " . $mini_ab;
                }
                $abstract_array[] = "$mini_ab";
            }
            $response['abstract'] = implode(' ', $abstract_array);

            $day = (string) $xml->PubmedBookArticle->BookDocument->Book->PubDate->Day;
            $month = (string) $xml->PubmedBookArticle->BookDocument->Book->PubDate->Month;
            $year = (string) $xml->PubmedBookArticle->BookDocument->Book->PubDate->Year;

            $response['year'] = '';
            if (!empty($year)) {
                if (empty($day))
                    $day = '01';
                if (empty($month))
                    $month = '01';
                $response['year'] = date('Y-m-d', strtotime($day . '-' . $month . '-' . $year));
            }

            $authors = $xml->PubmedBookArticle->BookDocument->AuthorList->Author;

            $name_array = array();
            if (!empty($authors)) {
                foreach ($authors as $author) {
                    $name_array[] = 'L:"' . (string) $author->LastName . '",F:"' . (string) $author->ForeName . '"';
                }
            }
            if (isset($name_array))
                $response['authors'] = join(";", $name_array);

            $editors = $xml->PubmedBookArticle->BookDocument->Book->AuthorList->Author;

            $name_array = array();
            if (!empty($editors)) {
                foreach ($editors as $editor) {
                    $name_array[] = 'L:"' . (string) $editor->LastName . '",F:"' . (string) $editor->ForeName . '"';
                }
            }
            if (isset($name_array))
                $response['editors'] = join(";", $name_array);
        }
    }

}

function record_unknown($dbHandle, $title, $file, $userID) {

    $query = "INSERT INTO library (file, title, title_ascii, addition_date, rating, added_by, bibtex)
             VALUES ((SELECT IFNULL((SELECT SUBSTR('0000' || CAST(MAX(id)+1 AS TEXT) || '.pdf',-9,9) FROM library),'00001.pdf')),
             :title, :title_ascii, :addition_date, :rating, :added_by,
             'unknown-0000-ID' || (SELECT IFNULL((SELECT MAX(id)+1 FROM library), 1)))";

    $stmt = $dbHandle->prepare($query);

    $stmt->bindParam(':title', $title, PDO::PARAM_STR);
    $stmt->bindParam(':title_ascii', $title_ascii, PDO::PARAM_STR);
    $stmt->bindParam(':addition_date', $addition_date, PDO::PARAM_STR);
    $stmt->bindParam(':rating', $rating, PDO::PARAM_INT);
    $stmt->bindParam(':added_by', $added_by, PDO::PARAM_INT);

    if (empty($title)) {
        $title = basename($file);
    }
    $file_extension = pathinfo($title, PATHINFO_EXTENSION);
    $title_ascii = utf8_deaccent($title);
    $addition_date = date('Y-m-d');
    $rating = 2;
    $added_by = intval($userID);

    $dbHandle->beginTransaction();

    $stmt->execute();
    $stmt = null;

    $id = $dbHandle->lastInsertId();
    $new_file = str_pad($id, 5, "0", STR_PAD_LEFT) . '.pdf';

    if (isset($_GET['shelf']) && !empty($userID)) {
        $user_query = $dbHandle->quote($userID);
        $file_query = $dbHandle->quote($id);
        $dbHandle->exec("INSERT OR IGNORE INTO shelves (userID,fileID) VALUES ($user_query,$file_query)");
    }

    if (isset($_GET['project']) && !empty($_GET['projectID'])) {
        $dbHandle->exec("INSERT OR IGNORE INTO projectsfiles (projectID,fileID) VALUES (" . intval($_GET['projectID']) . "," . intval($id) . ")");
    }

    ####### record new category into categories, if not exists #########

    if (isset($_GET['category2'])) {
        $category2 = $_GET['category2'];
    }
    $category2[] = '!unknown';
    $category_ids = array();

    $category2 = preg_replace('/\s{2,}/', '', $category2);
    $category2 = preg_replace('/^\s$/', '', $category2);
    $category2 = array_filter($category2);

    $query = "INSERT INTO categories (category) VALUES (:category)";
    $stmt = $dbHandle->prepare($query);
    $stmt->bindParam(':category', $new_category, PDO::PARAM_STR);

    while (list($key, $new_category) = each($category2)) {
        $new_category_quoted = $dbHandle->quote($new_category);
        $result = $dbHandle->query("SELECT categoryID FROM categories WHERE category=$new_category_quoted");
        $exists = $result->fetchColumn();
        $category_ids[] = $exists;
        $result = null;
        if (empty($exists)) {
            $stmt->execute();
            $category_ids[] = $dbHandle->lastInsertId();
        }
    }
    $stmt = null;

    ####### record new relations into filescategories #########

    $categories = array();
    $category_array = array();
    if (isset($_GET['category']))
        $category_array = $_GET['category'];

    if (!empty($category_array) || !empty($category_ids)) {
        $categories = array_merge((array) $category_array, (array) $category_ids);
        $categories = array_filter(array_unique($categories));
    }

    $query = "INSERT OR IGNORE INTO filescategories (fileID,categoryID) VALUES (:fileid,:categoryid)";

    $stmt = $dbHandle->prepare($query);
    $stmt->bindParam(':fileid', $id);
    $stmt->bindParam(':categoryid', $category_id);

    while (list($key, $category_id) = each($categories)) {
        if (!empty($id)) {
            $stmt->execute();
        }
    }
    $stmt = null;

    $dbHandle->commit();

    copy($file, IL_PDF_PATH . DIRECTORY_SEPARATOR . get_subfolder($new_file, IL_PDF_PATH) . DIRECTORY_SEPARATOR . $new_file);

    $hash = md5_file(IL_PDF_PATH . DIRECTORY_SEPARATOR . get_subfolder($new_file) . DIRECTORY_SEPARATOR . $new_file);

    //record office file into supplement
    if (in_array($file_extension, array('doc', 'docx', 'vsd', 'xls', 'xlsx', 'ppt', 'pptx', 'odt', 'ods', 'odp'))) {
        //record original file into supplement
        $supplement_filename = sprintf("%05d", intval($new_file)) . $title;
        copy(IL_TEMP_PATH . DIRECTORY_SEPARATOR . $title, IL_SUPPLEMENT_PATH . DIRECTORY_SEPARATOR . get_subfolder($new_file, IL_SUPPLEMENT_PATH) . DIRECTORY_SEPARATOR . $supplement_filename);
        unlink(IL_TEMP_PATH . DIRECTORY_SEPARATOR . $title);
    }

    //RECORD FILE HASH FOR DUPLICATE DETECTION
    if (!empty($hash)) {
        $hash = $dbHandle->quote($hash);
        $dbHandle->exec('UPDATE library SET filehash=' . $hash . ' WHERE id=' . $id);
    }

    $dbHandle = null;

    $error = recordFulltext($id, $new_file);

    $unpack_dir = IL_TEMP_PATH . DIRECTORY_SEPARATOR . $new_file;
    mkdir($unpack_dir);
    exec(select_pdfdetach() . ' -saveall -o ' . escapeshellarg($unpack_dir) . ' ' . escapeshellarg(IL_PDF_PATH . DIRECTORY_SEPARATOR . get_subfolder($new_file) . DIRECTORY_SEPARATOR . $new_file));
    $unpacked_files = array();
    $unpacked_files = scandir($unpack_dir);
    foreach ($unpacked_files as $unpacked_file) {
        if (is_file($unpack_dir . DIRECTORY_SEPARATOR . $unpacked_file)) {
            rename($unpack_dir . DIRECTORY_SEPARATOR . $unpacked_file, IL_SUPPLEMENT_PATH . DIRECTORY_SEPARATOR . get_subfolder($new_file, IL_SUPPLEMENT_PATH) . DIRECTORY_SEPARATOR . sprintf("%05d", intval($new_file)) . $unpacked_file);
        }
    }
    rmdir($unpack_dir);

}

/////////////show results//////////////////////

function show_search_results($result, $select, $shelf_files, $desktop_projects, $clip_files, $tempdbHandle, $search_term = '') {

    $project = '';
    if (!empty($_GET['project']))
        $project = $_GET['project'];
    $display = $_SESSION['display'];

    $i = 0;

    if ($display == 'icons')
        print '<table cellspacing=0 id="icon-container" style="border:0;width:100%">
        <tr><td class="alternating_row" style="width:100%;border-bottom:1px rgba(0,0,0,0.15) solid;border-top:1px rgba(0,0,0,0.15) solid;padding-bottom:11px">';

    while (list($key, $paper) = each($result)) {

        $pmid_url = '';
        $pmcid_url = '';
        $nasaads_url = '';
        $arxiv_url = '';
        $jstor_url = '';
        $other_urls = array();
        $urls = '';
        $uids = array();
        $pmid = '';
        $pmid_related_url = '';
        $pmid_citedby_pmc = '';
        $nasaid = '';
        $nasa_related_url = '';
        $nasa_citedby_pmc = '';
        $ieeeid = '';
        $pdf_search_term = '';

        // Highlight search results.
        if (!empty($search_term)) {
            $search_words = explode(' ', $search_term);
            $search_words = array_filter($search_words);
            $search_words_str = join('|', $search_words);
            foreach ($paper as $key => $value) {
                if ($key !== 'authors' && $key !== 'title' && $key !== 'abstract') {
                    continue;
                }
                $paper[$key] = preg_replace("/($search_words_str)/ui", '<span class="highlight-search">$1</span>', $value);
            }
            $pdf_search_term = $search_words[0];
        }

        if (!empty($paper['uid'])) {

            $uids = explode("|", $paper['uid']);

            while (list($key, $uid) = each($uids)) {

                if (preg_match('/PMID:/', $uid))
                    $pmid = preg_replace('/PMID:/', '', $uid);
                if (preg_match('/NASAADS:/', $uid))
                    $nasaid = preg_replace('/NASAADS:/', '', $uid);
                if (preg_match('/IEEE:/', $uid))
                    $ieeeid = preg_replace('/IEEE:/', '', $uid);
            }
        }

        if (!empty($paper['url'])) {

            $urls = explode("|", $paper['url']);

            while (list($key, $url) = each($urls)) {

                if (strpos($url, 'pubmed.org') !== false || strpos($url, '/pubmed/') !== false) {

                    $pmid_url = str_replace('pubmed.org', 'ncbi.nlm.nih.gov/pubmed', $url);

                } elseif (strpos($url, 'pubmedcentral.nih.gov') !== false || strpos($url, '/pmc/') !== false) {

                    $pmcid_url = $url;

                } elseif (strpos($url, 'adsabs.harvard.edu') !== false) {

                    $nasaads_url = $url;

                } elseif (strpos($url, 'arxiv.org') !== false) {

                    $arxiv_url = $url;

                } elseif (strpos($url, 'jstor.org') !== false) {

                    $jstor_url = $url;

                } else {

                    $other_urls[] = $url;
                }
            }
        }

        if (!empty($pmid)) {
            $pmid_related_url = 'https://www.ncbi.nlm.nih.gov/sites/entrez?db=pubmed&cmd=link&linkname=pubmed_pubmed&uid=' . $pmid;
            $pmid_citedby_pmc = 'https://www.ncbi.nlm.nih.gov/pubmed?db=pubmed&cmd=link&linkname=pubmed_pubmed_citedin&uid=' . $pmid;
        }

        if (!empty($nasaid)) {
            $nasa_related_url = 'http://adsabs.harvard.edu/cgi-bin/nph-abs_connect?return_req=no_params&text=' . urlencode($paper['abstract']) . '&title=' . urlencode($paper['title']);
            $nasa_citedby_pmc = 'http://adsabs.harvard.edu/cgi-bin/nph-data_query?bibcode=' . $nasaid . '&link_type=CITATIONS';
        }

        if (!empty($ieeeid)) {
            $ieee_url = 'http://ieeexplore.ieee.org/xpl/articleDetails.jsp?arnumber=' . $ieeeid;
        }

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
                    $new_authors[] = '<a href="display.php?select=' . $select . '&browse[' . urlencode($last . ', ' . $first) . ']=authors" class="navigation">'
                            . lib_htmlspecialchars($last . ', ' . $first) . '</a>';
                }
                $paper['authors'] = join('; ', $new_authors);
            }
        }

        $paper['journal'] = lib_htmlspecialchars($paper['journal']);
        $paper['title'] = lib_htmlspecialchars($paper['title']);
        $paper['abstract'] = lib_htmlspecialchars($paper['abstract']);
        $paper['year'] = lib_htmlspecialchars($paper['year']);

        #######new date#########
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

        $i = $i + 1;

        if ($display == 'icons') {

            if (!extension_loaded('gd'))
                die('<p>&nbsp;Error! Icon view requires GD extension and Ghostscript.</p>');

            $first_author = '&nbsp;';
            $auth_string = strip_tags($paper['authors']);
            $auth_arr = explode(",", $auth_string);
            if (!empty($auth_arr[0]))
                $first_author = $auth_arr[0];
            $etal = '';
            if (count($auth_arr) > 1)
                $etal = ', et al.';

            print '<div class="item-container thumb-items" id="display-item-' . $paper['id'] . '" data-file="' . $paper['file'] . '">'
                    . '<div>';

            print '<div class="thumb-titles">'
            . '<div>' . $paper['title'] . '</div>';

            print $first_author . $etal;
            if (!empty($paper['year']))
                print ' (' . substr($paper['year'], 0, 4) . ')';

            print '</div>';

            if (date('Y-m-d') == $paper['addition_date'])
                print '<div class="new-item ui-state-error-text">New!</div>';

            if (is_readable(IL_PDF_PATH . DIRECTORY_SEPARATOR . get_subfolder($paper['file']) . DIRECTORY_SEPARATOR . $paper['file'])) {

                if (isset($_SESSION['pdfviewer']) && $_SESSION['pdfviewer'] == 'external')
                    print '<a href="' . htmlspecialchars('pdfcontroller.php?downloadpdf=1&file=' . urlencode($paper['file']) . '#pagemode=none&scrollbar=1&navpanes=0&toolbar=1&statusbar=0&page=1&view=FitH,0&zoom=page-width') . '" target="_blank" style="display:block">';

                if (!isset($_SESSION['pdfviewer']) || (isset($_SESSION['pdfviewer']) && $_SESSION['pdfviewer'] == 'internal'))
                    print '<a href="' . htmlspecialchars('pdfviewer.php?file=' . urlencode($paper['file']) . '&title=' . urlencode(strip_tags ($paper['title']))) . '&search_term=' . urlencode ($pdf_search_term) . '" target="_blank" style="width:360px;height:240px;display:block">';

                print '<img src="icon.php?file=' . $paper['file'] . '" style="width:360px;height:240px;border:0" alt="Loading PDF..."></a>';
            } else {
                print '<div style="margin-top:90px;margin-left:150px;font-size:18px;color:#b5b6b8">No PDF</div>';
            }

            print '</div>';

            print PHP_EOL . '<table class="item-sticker" style="width:100%;border:1px solid rgba(0,0,0,0.15)"><tr><td class="noprint ui-corner-all" style="padding:0.5em 0.75em">';

            print '<div style="width:330px;white-space:nowrap;overflow:hidden;margin-bottom:0.25em">'
            . '<i class="fa fa-info-circle quick-view" style="font-size:1.25em"></i>&nbsp;&nbsp;&nbsp;';
            print '<i class="fa fa-external-link-square quick-view-external" style="font-size:1.25em"></i>&nbsp;&nbsp;&nbsp;';

            print '<span><i class="star ' . (($paper['rating'] >= 1) ? 'ui-state-error-text' : 'ui-priority-secondary') . ' fa fa-star" style="font-size:1.25em"></i>';
            print '&nbsp;<i class="star ' . (($paper['rating'] >= 2) ? 'ui-state-error-text' : 'ui-priority-secondary') . ' fa fa-star" style="font-size:1.25em"></i>';
            print '&nbsp;<i class="star ' . (($paper['rating'] == 3) ? 'ui-state-error-text' : 'ui-priority-secondary') . ' fa fa-star" style="font-size:1.25em"></i></span>&nbsp;&nbsp;';

            if (empty($paper['bibtex'])) {
                $bibtex_author = strip_tags($paper['authors']);
                $bibtex_author = substr($bibtex_author, 0, strpos($bibtex_author, ','));
                $bibtex_author = str_replace(array(' ', '{', '}'), '', $bibtex_author);
                $bibtex_author = str_replace(' ', '', $bibtex_author);
                if (empty($bibtex_author))
                    $bibtex_author = 'unknown';

                $bibtex_year = '0000';
                $bibtex_year_array = explode('-', $paper['year']);
                if (!empty($bibtex_year_array[0]))
                    $bibtex_year = $bibtex_year_array[0];

                $paper['bibtex'] = utf8_deaccent($bibtex_author) . '-' . $bibtex_year . '-ID' . $paper['id'];
            }

            echo '<input type="text" size="' . (strlen($paper['bibtex']) + 2) . '" class="bibtex" value="{' . htmlspecialchars($paper['bibtex']) . '}" readonly></div>';

            if (isset($shelf_files) && in_array($paper['id'], $shelf_files)) {
                print ' <span class="update_shelf clicked"><i class="update_shelf fa fa-check-square ui-state-error-text"></i>&nbsp;Shelf&nbsp;</span>';
            } else {
                print ' <span class="update_shelf"><i class="update_shelf fa fa-square-o"></i>&nbsp;Shelf&nbsp;</span>';
            }

            if (in_array($paper['id'], $clip_files)) {
                print ' &nbsp;<span class="update_clipboard clicked"><i class="update_clipboard fa fa-check-square ui-state-error-text"></i>&nbsp;Clipboard&nbsp;</span>';
            } else {
                print ' &nbsp;<span class="update_clipboard"><i class="update_clipboard fa fa-square-o"></i>&nbsp;Clipboard&nbsp;</span>';
            }

            foreach ($desktop_projects as $desktop_project) {

                $project_rowid = $tempdbHandle->query("SELECT ROWID FROM temp_projects WHERE projectID=" . intval($desktop_project['projectID']) . " AND fileID=" . intval($paper['id']) . " LIMIT 1");
                $project_rowid = $project_rowid->fetchColumn();

                if (!empty($project_rowid))
                    print ' <span data-projid="' . $desktop_project['projectID']
                            . '" class="update_project clicked" style="white-space:nowrap;padding-right:0.5em"><i class="update_project fa fa-check-square ui-state-error-text"></i> '
                            . htmlspecialchars($desktop_project['project']) . '</span>';

                if (empty($project_rowid))
                    print ' <span data-projid="' . $desktop_project['projectID']
                            . '" class="update_project" style="white-space:nowrap;padding-right:0.5em"><i class="update_project fa fa-square-o"></i> '
                            . htmlspecialchars($desktop_project['project']) . '</span>';

                $project_rowid = null;
            }

            print PHP_EOL . '</td></tr></table></div>';
        } else {

            print PHP_EOL . '<div id="display-item-' . $paper['id'] . '" class="item-container items" data-file="' . $paper['file'] . '" style="padding:0 0 0.75em 0">';

            print '<div class="ui-widget-header" style="overflow:hidden;border-left:0;border-right:0;padding:2px 6px">'
                    . '<div class="noprint titles-pdf" style="float:left"><i class="fa fa-info-circle quick-view" style="font-size:1em"></i>';
            echo '&nbsp;&nbsp;&nbsp;<i class="fa fa-external-link-square quick-view-external" style="font-size:1em"></i>&nbsp;</div>';

            if (is_readable(IL_PDF_PATH . DIRECTORY_SEPARATOR . get_subfolder($paper['file']) . DIRECTORY_SEPARATOR . $paper['file']) && isset($_SESSION['auth'])) {

                if (isset($_SESSION['pdfviewer']) && $_SESSION['pdfviewer'] == 'external')
                    print '<div class="noprint titles-pdf" style="float:left">
                        <a class="ui-state-error-text" href="' . htmlspecialchars('pdfcontroller.php?downloadpdf=1&file=' . urlencode($paper['file']) . '#pagemode=none&scrollbar=1&navpanes=0&toolbar=1&statusbar=0&page=1&view=FitH,0&zoom=page-width') . '" target="_blank" style="display:block">
                                PDF</a></div>';

                if (!isset($_SESSION['pdfviewer']) || (isset($_SESSION['pdfviewer']) && $_SESSION['pdfviewer'] == 'internal'))
                    print '<div class="noprint titles-pdf" style="float:left">
                        <a class="ui-state-error-text" href="' . htmlspecialchars('pdfviewer.php?file=' . urlencode($paper['file']) . '&title=' . urlencode(strip_tags ($paper['title']))) . '&search_term=' . urlencode ($pdf_search_term) . '" target="_blank" style="display:block">
                                PDF</a></div>';
            } else {
                print PHP_EOL . '<div class="noprint titles-pdf" style="float:left;color:rgba(0,0,0,0.3);cursor:auto">PDF</div>';
            }

            print PHP_EOL . '<div class="titles brief">&nbsp;' . $paper['title'] . '</div>';

            print '</div>';

            print '<div style="clear:both"></div>';

            print '<div style="margin:0.75em 2em 0 2em">';

            if (isset($shelf_files) && in_array($paper['id'], $shelf_files)) {
                print '<span class="update_shelf clicked"><i class="update_shelf fa fa-check-square ui-state-error-text"></i>&nbsp;Shelf&nbsp;</span>';
            } else {
                print '<span class="update_shelf"><i class="update_shelf fa fa-square-o"></i>&nbsp;Shelf&nbsp;</span>';
            }

            if (in_array($paper['id'], $clip_files)) {
                print ' &nbsp;<span class="update_clipboard clicked"><i class="update_clipboard fa fa-check-square ui-state-error-text"></i>&nbsp;Clipboard&nbsp;</span>';
            } else {
                print ' &nbsp;<span class="update_clipboard"><i class="update_clipboard fa fa-square-o"></i>&nbsp;Clipboard&nbsp;</span>';
            }

            foreach ($desktop_projects as $desktop_project) {

                $project_rowid = $tempdbHandle->query("SELECT ROWID FROM temp_projects WHERE projectID=" . intval($desktop_project['projectID']) . " AND fileID=" . intval($paper['id']) . " LIMIT 1");
                $project_rowid = $project_rowid->fetchColumn();

                if (!empty($project_rowid))
                    print ' <span data-projid="' . $desktop_project['projectID']
                            . '" class="update_project clicked" style="white-space:nowrap;padding-right:0.5em"><i class="update_project fa fa-check-square ui-state-error-text"></i> '
                            . htmlspecialchars($desktop_project['project']) . '</span>';

                if (empty($project_rowid))
                    print ' <span data-projid="' . $desktop_project['projectID']
                            . '" class="update_project" style="white-space:nowrap;padding-right:0.5em"><i class="update_project fa fa-square-o"></i> '
                            . htmlspecialchars($desktop_project['project']) . '</span>';

                $project_rowid = null;
            }

            print PHP_EOL . '</div>';

            if ($display == 'summary' || $display == 'abstract') {

                print PHP_EOL . '<div class="display-summary" style="margin:0.5em 2em 0 2em">';

                if (!empty($paper['authors']))
                    print PHP_EOL . '<div class="authors"><i class="author_expander fa fa-plus-circle"></i> ' . $paper['authors'] . '</div>';

                print (!empty($paper['journal']) ? $paper['journal'] : $paper['secondary_title']);

                print (!empty($date)) ? ' (' . htmlspecialchars($date) . ')' : '';

                if (!empty($paper['volume']))
                    print ' <b>' . htmlspecialchars($paper['volume']) . '</b>';

                if (!empty($paper['pages']))
                    print ': ' . htmlspecialchars($paper['pages']);

                if (date('Y-m-d') == $paper['addition_date']) {
                    $today = ' <span class="ui-state-error-text"><b>New!</b></span>';
                } else {
                    $today = '';
                }

                $result2 = $tempdbHandle->query("SELECT categoryID,category FROM temp_categories WHERE fileID=" . intval($paper['id']) . " ORDER BY category COLLATE NOCASE ASC");

                while ($categories = $result2->fetch(PDO::FETCH_ASSOC)) {

                    $category_array[] = '<a href="' . htmlspecialchars('display.php?browse[' . urlencode($categories['categoryID']) . ']=category&select=' . $select . '&project=' . $project) . '" class="navigation">'
                            . htmlspecialchars($categories['category']) . '</a>';
                }

                if (empty($category_array[0]))
                    $category_array[0] = '<a href="' . htmlspecialchars('display.php?browse[0]=category&select=' . $select)
                            . '" class="navigation">!unassigned</a>';

                print '<br><span><i class="star ' . (($paper['rating'] >= 1) ? 'ui-state-error-text' : 'ui-priority-secondary') . ' fa fa-star"></i>';
                print '&nbsp;<i class="star ' . (($paper['rating'] >= 2) ? 'ui-state-error-text' : 'ui-priority-secondary') . ' fa fa-star"></i>';
                print '&nbsp;<i class="star ' . (($paper['rating'] == 3) ? 'ui-state-error-text' : 'ui-priority-secondary') . ' fa fa-star"></i></span>&nbsp;';

                print '<b style="margin:0 0.5em">&middot;</b>';

                if (empty($paper['bibtex'])) {
                    $bibtex_author = strip_tags($paper['authors']);
                    $bibtex_author = substr($bibtex_author, 0, strpos($bibtex_author, ','));
                    $bibtex_author = str_replace(array(' ', '{', '}'), '', $bibtex_author);
                    $bibtex_author = str_replace(' ', '', $bibtex_author);
                    if (empty($bibtex_author))
                        $bibtex_author = 'unknown';

                    $bibtex_year = '0000';
                    $bibtex_year_array = explode('-', $paper['year']);
                    if (!empty($bibtex_year_array[0]))
                        $bibtex_year = $bibtex_year_array[0];

                    $paper['bibtex'] = utf8_deaccent($bibtex_author) . '-' . $bibtex_year . '-ID' . $paper['id'];
                }

                echo '<input type="text" size="' . (strlen($paper['bibtex']) + 2) . '" class="bibtex" value="{' . htmlspecialchars($paper['bibtex']) . '}" readonly>';

                print '<b style="margin:0 0.5em">&middot;</b>';

                print 'Category: ';

                $category_string = join(", ", $category_array);
                $category_array = null;

                print $category_string;

                print '<b style="margin:0 0.5em">&middot;</b> Added:&nbsp;<a href="display.php?select=' . $select . '&browse[' . $paper['addition_date'] . ']=addition_date" class="navigation">' . date('M jS, Y', strtotime($paper['addition_date'])) . '</a>' . $today;

                print '<div class="noprint">';

                if (!empty($pmid_url)) {
                    print '<a href="' . htmlspecialchars($pmid_url) . '" target="_blank">PubMed</a> <b style="margin:0 0.5em">&middot;</b> ';
                }

                if (!empty($pmid_related_url)) {
                    print '<a href="' . htmlspecialchars($pmid_related_url) . '" target="_blank">Related Articles</a> <b style="margin:0 0.5em">&middot;</b> ';
                }

                if (!empty($pmid_citedby_pmc)) {
                    print '<a href="' . htmlspecialchars($pmid_citedby_pmc) . '" target="_blank">Cited by</a> <b style="margin:0 0.5em">&middot;</b> ';
                }

                if (!empty($pmcid_url)) {
                    print '<a href="' . htmlspecialchars($pmcid_url) . '" target="_blank">PubMed Central</a> <b style="margin:0 0.5em">&middot;</b> ';
                }

                if (!empty($nasaads_url)) {
                    print '<a href="' . htmlspecialchars($nasaads_url) . '" target="_blank">NASA ADS</a> <b style="margin:0 0.5em">&middot;</b> ';
                }

                if (!empty($nasa_related_url)) {
                    print '<a href="' . htmlspecialchars($nasa_related_url) . '" target="_blank">Related Articles</a> <b style="margin:0 0.5em">&middot;</b> ';
                }

                if (!empty($nasa_citedby_pmc)) {
                    print '<a href="' . htmlspecialchars($nasa_citedby_pmc) . '" target="_blank">Cited by</a> <b style="margin:0 0.5em">&middot;</b> ';
                }

                if (!empty($arxiv_url)) {
                    print '<a href="' . htmlspecialchars($arxiv_url) . '" target="_blank">arXiv</a> <b style="margin:0 0.5em">&middot;</b> ';
                }

                if (!empty($jstor_url)) {
                    print '<a href="' . htmlspecialchars($jstor_url) . '" target="_blank">JSTOR</a> <b style="margin:0 0.5em">&middot;</b> ';
                }

                if (!empty($ieee_url)) {
                    print '<a href="' . htmlspecialchars($ieee_url) . '" target="_blank">IEEE</a> <b style="margin:0 0.5em">&middot;</b> ';
                }

                if (!empty($paper['doi'])) {
                    print '<a href="' . htmlspecialchars('http://dx.doi.org/' . urlencode($paper['doi'])) . '" target="_blank">Publisher Website</a> <b style="margin:0 0.5em">&middot;</b> ';
                }

                if (!empty($other_urls)) {
                    foreach ($other_urls as $another_url) {
                        print '<a href="' . htmlspecialchars($another_url) . '" target="_blank" class="anotherurl">' . htmlspecialchars(parse_url($another_url, PHP_URL_HOST)) . '</a> <b style="margin:0 0.5em">&middot;</b> ';
                    }
                }

                print '<a href="stable.php?id=' . $paper['id'] . '" target="_blank">Stable Link</a>';

                print '</div></div>';
            }

            if ($display == 'abstract') {
                print '<div class="abstract" style="margin:0 2em">';
                print $paper['abstract'] . '</div>';
            }

            print '</div>';
        }
    }
    if ($display == 'icons')
        print '</td></tr></table>';

}

// Read shelf function serves to output those files from the tested array
// that are in Shelf. This function does not serve to dump the whole Shelf
// into an array. It would not scale up.

function read_shelf($dbHandle, $id_array) {

    if (isset($_SESSION['auth'])) {

        // Convert all types to array.
        $id_array = (array) $id_array;

        // Prepare query.
        $id_array2 = array();
        foreach ($id_array as $id) {
            $id_array2[] = $dbHandle->quote($id);
        }
        $id_string = join(",", $id_array2);
        $user_query = $dbHandle->quote($_SESSION['user_id']);

        // Query database.
        $shelf_result = $dbHandle->query("SELECT fileID FROM shelves WHERE fileID IN ($id_string) AND userID=$user_query LIMIT 1000");
        $shelf_files = $shelf_result->fetchAll(PDO::FETCH_COLUMN);
        $shelf_result = null;

        return $shelf_files;
    }

}

/////////////read desktop/////////////////////////

function read_desktop($dbHandle) {

    if (isset($_GET['select']) && $_GET['select'] == 'desk') {
        $active = '';
    } else {
        $active = " AND projects.active=1";
    }

    if (isset($_SESSION['auth'])) {
        $files_array = array();
        $id_query = $dbHandle->quote($_SESSION['user_id']);
        $query = $dbHandle->query("SELECT DISTINCT projects.projectID AS projectID,project FROM projects
                        LEFT OUTER JOIN projectsusers ON projects.projectID=projectsusers.projectID
                        WHERE (projects.userID=$id_query OR projectsusers.userID=$id_query) $active ORDER BY project COLLATE NOCASE ASC");
        $files_array = $query->fetchAll(PDO::FETCH_ASSOC);
        $query = null;
        return $files_array;
    }

}

/////////////update notes/////////////////////////

function update_notes($fileID, $new_notes, $dbHandle) {

    $notesID = '';
    $userID = $dbHandle->quote($_SESSION['user_id']);
    $fileID = $dbHandle->quote($fileID);

    $dbHandle->beginTransaction();

    $result = $dbHandle->query("SELECT notesID FROM notes WHERE userID=" . $userID . " AND fileID=" . $fileID);
    $notesID = $result->fetchColumn();
    $result = null;

    if (empty($notesID) && !empty($new_notes)) {
        $new_notes = $dbHandle->quote($new_notes);
        $dbHandle->exec("INSERT INTO notes (userID,fileID,notes) VALUES ($userID,$fileID,$new_notes)");
    } elseif (!empty($notesID)) {
        $dbHandle->exec("DELETE FROM notes WHERE notesID=$notesID");
        if (!empty($new_notes)) {
            $new_notes = $dbHandle->quote($new_notes);
            $dbHandle->exec("INSERT INTO notes (notesID,userID,fileID,notes) VALUES ($notesID,$userID,$fileID,$new_notes)");
        }
    }
    $dbHandle->commit();

}

#check nobody uses the record no shelfs no projects
#if no, delete record from table library, notes, attachments
#delete full text file and attachments

function delete_record($dbHandle, $files) {

    settype($files, "array");

    // get PDF filenames of deleted items
    $result = $dbHandle->query("SELECT file FROM library WHERE id IN (" . join(',', $files) . ")");
    $filenames = $result->fetchAll(PDO::FETCH_COLUMN);
    $result = null;

    // delete PDFs, PDF cache, supplementary files and images
    while (list(, $filename) = each($filenames)) {

        $pdf_path = IL_PDF_PATH . DIRECTORY_SEPARATOR . get_subfolder($filename) . DIRECTORY_SEPARATOR;

        if (is_file($pdf_path . $filename))
            unlink($pdf_path . $filename);

        if (is_file(IL_PDF_CACHE_PATH . DIRECTORY_SEPARATOR . $filename . '.sq3'))
            unlink(IL_PDF_CACHE_PATH . DIRECTORY_SEPARATOR . $filename . '.sq3');

        $integer1 = sprintf("%05d", intval($filename));

        $supplementary_files = glob(IL_SUPPLEMENT_PATH . DIRECTORY_SEPARATOR . get_subfolder($filename) . DIRECTORY_SEPARATOR . $integer1 . '*', GLOB_NOSORT);
        if (is_array($supplementary_files)) {
            foreach ($supplementary_files as $supplementary_file) {
                @unlink($supplementary_file);
            }
        }
        $png_files = glob(IL_IMAGE_PATH . DIRECTORY_SEPARATOR . $integer1 . '*.jpg', GLOB_NOSORT);
        if (is_array($png_files)) {
            foreach ($png_files as $png_file) {
                @unlink($png_file);
            }
        }
    }

    // delete from clipboard, make sure session_write_close was not called before this
    attach_clipboard($dbHandle);
    $dbHandle->exec("DELETE FROM clipboard.files WHERE id IN (" . join(',', $files) . ")");
    $dbHandle->exec("DETACH DATABASE clipboard");

    // delete from main database
    $dbHandle->beginTransaction();
    $dbHandle->exec("DELETE FROM library WHERE id IN (" . join(',', $files) . ")");
    $dbHandle->exec("DELETE FROM shelves WHERE fileID IN (" . join(',', $files) . ")");
    $dbHandle->exec("DELETE FROM filescategories WHERE fileID IN (" . join(',', $files) . ")");
    $dbHandle->exec("DELETE FROM projectsfiles WHERE fileID IN (" . join(',', $files) . ")");
    $dbHandle->exec("DELETE FROM notes WHERE fileID IN (" . join(',', $files) . ")");
    $dbHandle->exec("DELETE FROM yellowmarkers WHERE filename IN ('" . join("','", $filenames) . "')");
    $dbHandle->exec("DELETE FROM annotations WHERE filename IN ('" . join("','", $filenames) . "')");
    $dbHandle->commit();
    $dbHandle = null;

    // delete full texts
    $fdbHandle = database_connect(IL_DATABASE_PATH, 'fulltext');
    $fdbHandle->exec("DELETE FROM full_text WHERE fileID IN (" . join(',', $files) . ")");
    $fdbHandle = null;

    // delete discussions
    if (file_exists(IL_DATABASE_PATH . DIRECTORY_SEPARATOR . 'discussions.sq3')) {

        $fdbHandle = database_connect(IL_DATABASE_PATH, 'discussions');
        $fdbHandle->exec("DELETE FROM filediscussion WHERE fileID IN (" . join(',', $files) . ")");
        $fdbHandle = null;
    }

    // delete PDF bookmarks and history
    if (file_exists(IL_DATABASE_PATH . DIRECTORY_SEPARATOR . 'history.sq3')) {

        $fdbHandle = database_connect(IL_DATABASE_PATH, 'history');
        $fdbHandle->beginTransaction();
        $fdbHandle->exec("DELETE FROM usersfiles WHERE fileID IN (" . join(',', $files) . ")");
        $fdbHandle->exec("DELETE FROM bookmarks WHERE file IN ('" . join("','", $filenames) . "')");
        $fdbHandle->commit();
        $fdbHandle = null;
    }

    if (!empty($error))
        return $error;

}

function save_setting($dbHandle, $setting_name, $setting_value) {

    if (!empty($_SESSION['user_id'])) {
        $userID = intval($_SESSION['user_id']);
    }

    if (!empty($_REQUEST['userID'])) {
        $userID = intval($_REQUEST['userID']);
    }

    if (strpos($setting_name, 'global_') === 0) {
        $userID = '';
        // Remove global_ prefix.
        $setting_name = substr($setting_name, 7);
    }

    $dbHandle->beginTransaction();

    $stmt = $dbHandle->prepare("DELETE FROM settings"
            . " WHERE userID=:userID AND setting_name=:setting_name");

    $stmt->bindParam(':userID', $userID, PDO::PARAM_STR);
    $stmt->bindParam(':setting_name', $setting_name, PDO::PARAM_STR);

    // Delete old setting value.
    $stmt->execute();

    if (isset($_SESSION[$setting_name])) {
        unset($_SESSION[$setting_name]);
    }

    if (!empty($setting_value)) {

        $stmt2 = $dbHandle->prepare("INSERT INTO settings (userID,setting_name,setting_value)"
                . " VALUES (:userID,:setting_name,:setting_value)");

        $stmt2->bindParam(':userID', $userID, PDO::PARAM_STR);
        $stmt2->bindParam(':setting_name', $setting_name, PDO::PARAM_STR);
        $stmt2->bindParam(':setting_value', $setting_value, PDO::PARAM_STR);

        $stmt2->execute();
        $_SESSION[$setting_name] = $setting_value;
    }

    $dbHandle->commit();

}

function save_settings($dbHandle, array $settings) {

    if (!empty($_SESSION['user_id'])) {
        $userID = intval($_SESSION['user_id']);
    }

    if (!empty($_GET['userID'])) {
        $userID = intval($_REQUEST['userID']);
    }

    $dbHandle->beginTransaction();

    $stmt = $dbHandle->prepare("DELETE FROM settings"
            . " WHERE userID=:userID AND setting_name=:setting_name");

    $stmt->bindParam(':userID', $userID, PDO::PARAM_STR);
    $stmt->bindParam(':setting_name', $name, PDO::PARAM_STR);

    $stmt2 = $dbHandle->prepare("INSERT INTO settings (userID,setting_name,setting_value)"
            . " VALUES (:userID,:setting_name,:setting_value)");

    $stmt2->bindParam(':userID', $userID, PDO::PARAM_STR);
    $stmt2->bindParam(':setting_name', $name, PDO::PARAM_STR);
    $stmt2->bindParam(':setting_value', $value, PDO::PARAM_STR);

    foreach ($settings as $name => $value) {

        if (strpos($name, 'global_') === 0) {
            $userID = '';
            // Remove global_ prefix.
            $name = substr($name, 7);
        }

        // Delete old setting value.
        $stmt->execute();
        if (isset($_SESSION[$name])) {
            unset($_SESSION[$name]);
        }

        // Save new setting value, if not empty.
        if (!empty($value)) {
            $stmt2->execute();
            $_SESSION[$name] = $value;
        }
    }

    $dbHandle->commit();

}

function get_setting($setting_name) {

    if (isset($_SESSION[$setting_name])) {
        return $_SESSION[$setting_name];
    } else {
        return '';
    }

}

function utf8_deaccent($string) {

    $UTF8_a = array(
        "/\xc3\xa0/u", "/\xc3\xa1/u", "/\xc3\xa2/u", "/\xc3\xa3/u", "/\xc3\xa4/u", "/\xc3\xa5/u", "/\xc3\xa6/u",
        "/\xc4\x81/u", "/\xc4\x83/u", "/\xc4\x85/u", "/\xc7\x8e/u", "/\xc7\x9f/u", "/\xc7\xa1/u", "/\xc7\xa3/u",
        "/\xc7\xbb/u", "/\xc7\xbd/u", "/\xc8\x81/u", "/\xc8\x83/u", "/\xc8\xa7/u"
    );

    $UTF8_b = array(
        "/\xc6\x80/u", "/\xc6\x83/u", "/\xc9\x93/u"
    );

    $UTF8_c = array(
        "/\xc3\xa7/u", "/\xc4\x87/u", "/\xc4\x89/u", "/\xc4\x8b/u", "/\xc4\x8d/u", "/\xc6\x88/u", "/\xc8\xbc/u", "/\xc9\x95/u"
    );

    $UTF8_d = array(
        "/\xc4\x8f/u", "/\xc4\x91/u", "/\xc6\x8c/u", "/\xc8\xa1/u", "/\xc9\x96/u", "/\xc9\x97/u"
    );

    $UTF8_e = array(
        "/\xc3\xa8/u", "/\xc3\xa9/u", "/\xc3\xaa/u", "/\xc3\xab/u", "/\xc4\x93/u", "/\xc4\x95/u",
        "/\xc4\x97/u", "/\xc4\x99/u", "/\xc4\x9b/u", "/\xc8\x85/u", "/\xc8\x87/u", "/\xc8\xa9/u", "/\xc9\x87/u"
    );

    $UTF8_f = array(
        "/\xc6\x92/u"
    );

    $UTF8_g = array(
        "/\xc4\x9d/u", "/\xc4\x9f/u", "/\xc4\xa1/u", "/\xc4\xa3/u", "/\xc7\xa5/u", "/\xc7\xa7/u", "/\xc7\xb5/u", "/\xc9\xa0/u"
    );

    $UTF8_h = array(
        "/\xc4\xa5/u", "/\xc4\xa7/u", "/\xc8\x9f/u", "/\xc9\xa6/u"
    );

    $UTF8_i = array(
        "/\xc3\xac/u", "/\xc3\xad/u", "/\xc3\xae/u", "/\xc3\xaf/u", "/\xc4\xa9/u", "/\xc4\xab/u", "/\xc4\xad/u",
        "/\xc4\xaf/u", "/\xc4\xb1/u", "/\xc7\x90/u", "/\xc8\x89/u", "/\xc8\x8b/u", "/\xc9\xa8/u"
    );

    $UTF8_j = array(
        "/\xc4\xb5/u", "/\xc7\xb0/u", "/\xc9\x89/u"
    );

    $UTF8_k = array(
        "/\xc4\xb7/u", "/\xc6\x99/u", "/\xc7\xa9/u"
    );

    $UTF8_l = array(
        "/\xc4\xba/u", "/\xc4\xbc/u", "/\xc4\xbe/u", "/\xc5\x80/u", "/\xc5\x82/u",
        "/\xc6\x9a/u", "/\xc8\xb4/u", "/\xc9\xab/u", "/\xc9\xac/u", "/\xc9\xad/u"
    );

    $UTF8_m = array(
        "/\xc9\xb1/u"
    );

    $UTF8_n = array(
        "/\xc3\xb1/u", "/\xc5\x84/u", "/\xc5\x86/u", "/\xc5\x88/u", "/\xc5\x89/u",
        "/\xc6\x9e/u", "/\xc7\xb9/u", "/\xc8\xb5/u", "/\xc9\xb2/u", "/\xc9\xb3/u"
    );

    $UTF8_o = array(
        "/\xc3\xb2/u", "/\xc3\xb3/u", "/\xc3\xb4/u", "/\xc3\xb5/u", "/\xc3\xb6/u", "/\xc3\xb8/u", "/\xc5\x8d/u",
        "/\xc5\x8f/u", "/\xc5\x91/u", "/\xc6\xa1/u", "/\xc7\x92/u", "/\xc7\xab/u", "/\xc7\xad/u", "/\xc7\xbf/u",
        "/\xc8\x8d/u", "/\xc8\x8f/u", "/\xc8\xab/u", "/\xc8\xad/u", "/\xc8\xaf/u", "/\xc8\xb1/u", "/\xc9\x94/u"
    );

    $UTF8_p = array(
        "/\xc6\xa5/u"
    );

    $UTF8_q = array(
        "/\xc9\x8b/u"
    );

    $UTF8_r = array(
        "/\xc5\x95/u", "/\xc5\x97/u", "/\xc5\x99/u", "/\xc8\x91/u", "/\xc8\x93/u",
        "/\xc9\x8d/u", "/\xc9\xbc/u", "/\xc9\xbd/u", "/\xc9\xbe/u", "/\xc9\xbf/u"
    );

    $UTF8_s = array(
        "/\xc3\x9f/u", "/\xc5\x9b/u", "/\xc5\x9d/u", "/\xc5\x9f/u", "/\xc5\xa1/u", "/\xc8\x99/u", "/\xc8\xbf/u"
    );

    $UTF8_t = array(
        "/\xc5\xa3/u", "/\xc5\xa5/u", "/\xc5\xa7/u", "/\xc6\xab/u", "/\xc6\xad/u", "/\xc8\x9b/u", "/\xc8\xb6/u"
    );

    $UTF8_u = array(
        "/\xc3\xb9/u", "/\xc3\xba/u", "/\xc3\xbb/u", "/\xc3\xbc/u", "/\xc5\xab/u", "/\xc5\xad/u", "/\xc5\xaf/u", "/\xc5\xb1/u", "/\xc5\xb3/u",
        "/\xc6\xb0/u", "/\xc7\x94/u", "/\xc7\x96/u", "/\xc7\x98/u", "/\xc7\x9a/u", "/\xc7\x9c/u", "/\xc8\x95/u", "/\xc8\x97/u"
    );

    $UTF8_w = array(
        "/\xc5\xb5/u"
    );

    $UTF8_y = array(
        "/\xc3\xbd/u", "/\xc3\xbf/u", "/\xc5\xb7/u", "/\xc6\xb4/u", "/\xc8\xb3/u", "/\xc9\x8f/u"
    );

    $UTF8_z = array(
        "/\xc5\xba/u", "/\xc5\xbc/u", "/\xc5\xbe/u", "/\xc6\xb6/u", "/\xc8\xa5/u", "/\xc9\x80/u"
    );

    $UTF8_A = array(
        "/\xc3\x80/u", "/\xc3\x81/u", "/\xc3\x82/u", "/\xc3\x83/u", "/\xc3\x84/u", "/\xc3\x85/u", "/\xc3\x86/u", "/\xc4\x80/u", "/\xc4\x82/u",
        "/\xc4\x84/u", "/\xc7\x8d/u", "/\xc7\x9e/u", "/\xc7\xa0/u", "/\xc7\xa2/u", "/\xc7\xba/u", "/\xc7\xbc/u", "/\xc8\x80/u", "/\xc8\x82/u"
    );

    $UTF8_B = array(
        "/\xc6\x81/u", "/\xc6\x82/u", "/\xc9\x83/u"
    );

    $UTF8_C = array(
        "/\xc3\x87/u", "/\xc4\x86/u", "/\xc4\x88/u", "/\xc4\x8a/u", "/\xc4\x8c/u", "/\xc6\x87/u", "/\xc8\xbb/u"
    );

    $UTF8_D = array(
        "/\xc4\x8e/u", "/\xc4\x90/u", "/\xc6\x89/u", "/\xc6\x8a/u", "/\xc6\x8b/u"
    );

    $UTF8_E = array(
        "/\xc3\x88/u", "/\xc3\x89/u", "/\xc3\x8a/u", "/\xc3\x8b/u", "/\xc4\x92/u", "/\xc4\x94/u", "/\xc4\x96/u",
        "/\xc4\x98/u", "/\xc4\x9a/u", "/\xc8\x84/u", "/\xc8\x86/u", "/\xc8\xa8/u", "/\xc9\x86/u"
    );

    $UTF8_F = array(
        "/\xc6\x91/u"
    );

    $UTF8_G = array(
        "/\xc4\x9c/u", "/\xc4\x9e/u", "/\xc4\xa0/u", "/\xc4\xa2/u", "/\xc6\x93/u", "/\xc7\xa4/u", "/\xc7\xa6/u", "/\xc7\xb4/u"
    );

    $UTF8_H = array(
        "/\xc4\xa4/u", "/\xc4\xa6/u", "/\xc8\x9e/u"
    );

    $UTF8_I = array(
        "/\xc3\x8c/u", "/\xc3\x8d/u", "/\xc3\x8e/u", "/\xc3\x8f/u", "/\xc4\xa8/u", "/\xc4\xaa/u", "/\xc4\xac/u",
        "/\xc4\xae/u", "/\xc4\xb0/u", "/\xc6\x97/u", "/\xc7\x8f/u", "/\xc8\x88/u", "/\xc8\x8a/u"
    );

    $UTF8_J = array(
        "/\xc4\xb4/u", "/\xc9\x88/u"
    );

    $UTF8_K = array(
        "/\xc4\xb6/u", "/\xc6\x98/u", "/\xc7\xa8/u"
    );

    $UTF8_L = array(
        "/\xc4\xb9/u", "/\xc4\xbb/u", "/\xc4\xbd/u", "/\xc4\xbf/u", "/\xc5\x81/u", "/\xc8\xbd/u"
    );

    $UTF8_N = array(
        "/\xc3\x91/u", "/\xc5\x83/u", "/\xc5\x85/u", "/\xc5\x87/u", "/\xc6\x9d/u", "/\xc7\xb8/u", "/\xc8\xa0/u"
    );

    $UTF8_O = array(
        "/\xc3\x92/u", "/\xc3\x93/u", "/\xc3\x94/u", "/\xc3\x95/u", "/\xc3\x96/u", "/\xc3\x98/u", "/\xc5\x8c/u", "/\xc5\x8e/u",
        "/\xc5\x90/u", "/\xc5\x92/u", "/\xc6\x86/u", "/\xc6\x9f/u", "/\xc6\xa0/u", "/\xc7\x91/u", "/\xc7\xaa/u", "/\xc7\xac/u",
        "/\xc7\xbe/u", "/\xc8\x8c/u", "/\xc8\x8e/u", "/\xc8\xaa/u", "/\xc8\xac/u", "/\xc8\xae/u", "/\xc8\xb0/u"
    );

    $UTF8_P = array(
        "/\xc6\xa4/u"
    );

    $UTF8_R = array(
        "/\xc5\x94/u", "/\xc5\x96/u", "/\xc5\x98/u", "/\xc8\x90/u", "/\xc8\x92/u", "/\xc9\x8c/u"
    );

    $UTF8_S = array(
        "/\xc5\x9a/u", "/\xc5\x9c/u", "/\xc5\x9e/u", "/\xc5\xa0/u", "/\xc8\x98/u"
    );

    $UTF8_T = array(
        "/\xc5\xa2/u", "/\xc5\xa4/u", "/\xc5\xa6/u", "/\xc6\xac/u", "/\xc6\xae/u", "/\xc8\x9a/u", "/\xc8\xbe/u"
    );

    $UTF8_U = array(
        "/\xc3\x99/u", "/\xc3\x9a/u", "/\xc3\x9b/u", "/\xc3\x9c/u", "/\xc5\xa8/u", "/\xc5\xaa/u", "/\xc5\xac/u", "/\xc5\xae/u",
        "/\xc5\xb0/u", "/\xc5\xb2/u", "/\xc6\xaf/u", "/\xc7\x93/u", "/\xc7\x95/u", "/\xc7\x97/u", "/\xc7\x99/u", "/\xc7\x9b/u",
        "/\xc8\x94/u", "/\xc8\x96/u", "/\xc9\x84/u"
    );

    $UTF8_V = array(
        "/\xc6\xb2/u"
    );

    $UTF8_W = array(
        "/\xc5\xb4/u"
    );

    $UTF8_Y = array(
        "/\xc3\x9d/u", "/\xc5\xb6/u", "/\xc5\xb8/u", "/\xc6\xb3/u", "/\xc8\xb2/u", "/\xc9\x8e/u"
    );

    $UTF8_Z = array(
        "/\xc5\xb9/u", "/\xc5\xbb/u", "/\xc5\xbd/u", "/\xc6\xb5/u", "/\xc8\xa4/u"
    );

    $string = preg_replace($UTF8_a, 'a', $string);
    $string = preg_replace($UTF8_b, 'b', $string);
    $string = preg_replace($UTF8_c, 'c', $string);
    $string = preg_replace($UTF8_d, 'd', $string);
    $string = preg_replace($UTF8_e, 'e', $string);
    $string = preg_replace($UTF8_f, 'f', $string);
    $string = preg_replace($UTF8_g, 'g', $string);
    $string = preg_replace($UTF8_h, 'h', $string);
    $string = preg_replace($UTF8_i, 'i', $string);
    $string = preg_replace($UTF8_j, 'j', $string);
    $string = preg_replace($UTF8_k, 'k', $string);
    $string = preg_replace($UTF8_l, 'l', $string);
    $string = preg_replace($UTF8_m, 'm', $string);
    $string = preg_replace($UTF8_n, 'n', $string);
    $string = preg_replace($UTF8_o, 'o', $string);
    $string = preg_replace($UTF8_p, 'p', $string);
    $string = preg_replace($UTF8_q, 'q', $string);
    $string = preg_replace($UTF8_r, 'r', $string);
    $string = preg_replace($UTF8_s, 's', $string);
    $string = preg_replace($UTF8_t, 't', $string);
    $string = preg_replace($UTF8_u, 'u', $string);
    $string = preg_replace($UTF8_w, 'w', $string);
    $string = preg_replace($UTF8_y, 'y', $string);
    $string = preg_replace($UTF8_z, 'z', $string);

    $string = preg_replace($UTF8_A, 'A', $string);
    $string = preg_replace($UTF8_B, 'B', $string);
    $string = preg_replace($UTF8_C, 'C', $string);
    $string = preg_replace($UTF8_D, 'D', $string);
    $string = preg_replace($UTF8_E, 'E', $string);
    $string = preg_replace($UTF8_F, 'F', $string);
    $string = preg_replace($UTF8_G, 'G', $string);
    $string = preg_replace($UTF8_H, 'H', $string);
    $string = preg_replace($UTF8_I, 'I', $string);
    $string = preg_replace($UTF8_J, 'J', $string);
    $string = preg_replace($UTF8_K, 'K', $string);
    $string = preg_replace($UTF8_L, 'L', $string);
    $string = preg_replace($UTF8_N, 'N', $string);
    $string = preg_replace($UTF8_O, 'O', $string);
    $string = preg_replace($UTF8_P, 'P', $string);
    $string = preg_replace($UTF8_R, 'R', $string);
    $string = preg_replace($UTF8_S, 'S', $string);
    $string = preg_replace($UTF8_T, 'T', $string);
    $string = preg_replace($UTF8_U, 'U', $string);
    $string = preg_replace($UTF8_V, 'V', $string);
    $string = preg_replace($UTF8_W, 'W', $string);
    $string = preg_replace($UTF8_Y, 'Y', $string);
    $string = preg_replace($UTF8_Z, 'Z', $string);

    return $string;

}

/////////////mobile show results//////////////////////

function mobile_show_search_results($result, $clip_files) {

    $i = 0;
    $display = $_SESSION['display'];

    if ($display == 'icons') {
        print '<table id="icon-container">
        <tr><td>';
    } else {
        print '<div data-role="collapsible-set" data-inset="false" data-theme="a" data-content-theme="a">';
    }

    while (list($key, $paper) = each($result)) {

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

        $paper['authors'] = htmlspecialchars($paper['authors']);
        $paper['title'] = htmlspecialchars($paper['title']);
        $paper['year'] = htmlspecialchars($paper['year']);

        $first_author = '&nbsp;';
        $auth_arr = explode(';', $paper['authors']);
        $auth_arr2 = explode(',', $auth_arr[0]);
        if (!empty($auth_arr2[0]))
            $first_author = $auth_arr2[0];
        $etal = '';
        if (count($auth_arr) > 1)
            $etal = ', et al.';

        #######new date#########
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

        $i = $i + 1;

        if ($display == 'icons') {

            if (!extension_loaded('gd'))
                die('<p>&nbsp;Error! Icon view requires GD extension and Ghostscript.</p>');

            print '<div class="thumb-items">';

            if (is_readable(IL_PDF_PATH . DIRECTORY_SEPARATOR . get_subfolder($paper['file']) . DIRECTORY_SEPARATOR . $paper['file']))
                print '<a href="' . htmlspecialchars('pdfcontroller.php?downloadpdf=1&file=' . urlencode($paper['file']) . '#pagemode=none&scrollbar=1&navpanes=0&toolbar=1&statusbar=0&page=1&view=FitH,0&zoom=page-width') . '" target="_blank" style="display:block;text-decoration:none">';

            print '<div class="thumb-items-top"><div class="thumb-titles"><div>' . $paper['title'] . '<br>';
            if (!empty($paper['year']))
                print '(' . substr($paper['year'], 0, 4) . ')';
            print '</div></div>';

            if (is_readable(IL_PDF_PATH . DIRECTORY_SEPARATOR . get_subfolder($paper['file']) . DIRECTORY_SEPARATOR . $paper['file'])) {

                print '</a><a href="' . htmlspecialchars('pdfcontroller.php?downloadpdf=1&file=' . urlencode($paper['file']) . '#pagemode=none&scrollbar=1&navpanes=0&toolbar=1&statusbar=0&page=1&view=FitH,0&zoom=page-width') . '" target="_blank" style="display:block">';
                print '<img src="icon.php?file=' . $paper['file'] . '" style="width:100%;border:0" alt="Loading PDF..."></a>';
            } else {
                print '<div style="text-align:center;margin-top:90px;font-size:18px;color:#b5b6b8">No PDF</div>';
            }

            print '</div>';

            print '<form><input class="update_clipboard" name="checkbox-clipboard" id="checkbox-clipboard-' . $paper['id'] . '" type="checkbox" data-mini="false"';

            if (in_array($paper['id'], $clip_files))
                print ' checked="checked"';

            print '><label for="checkbox-clipboard-' . $paper['id'] . '"><span style="font-size:0.8em">Clipboard</span></label></form>';

            print PHP_EOL . '</div></div>';
        } else {

            print PHP_EOL . '<div data-role="collapsible">';

            print PHP_EOL . '<h4 class="accordeon" data-fileid="' . $paper['id'] . '">' . $paper['title'] . '</h4>';

            print '<div style="padding:0 0px"></div></div>';
        }
    }
    if ($display == 'icons') {
        print '</td></tr></table>';
    } else {
        print '</div>';
    }

}
