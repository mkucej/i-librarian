<?php

// ALLOW SUB, SUP, AND MATHML
function lib_htmlspecialchars($input) {
    $input = htmlspecialchars($input);
    $arr = array('math', 'maction', 'maligngroup', 'malignmark', 'menclose', 'merror',
        'mfenced', 'mfrac', 'mglyph', 'mi', 'mlabeledtr', 'mlongdiv',
        'mmultiscripts', 'mn', 'mo,mover', 'mpadded', 'mphantom', 'mroot', 'mrow',
        'ms', 'mscarries', 'mscarry', 'msgroup', 'msline', 'mspace', 'msqrt', 'msrow',
        'mstack', 'mstyle', 'msub', 'msup', 'msubsup', 'mtable', 'mtd', 'mtext', 'mtr',
        'munder', 'munderover', 'sub', 'sup');
    foreach ($arr as $tag) {
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
            'endnote' => 'Journal Article'
        ),
        array(
            'ilib' => 'book',
            'bibtex' => 'book',
            'ris' => 'BOOK',
            'endnote' => 'Book'
        ),
        array(
            'ilib' => 'chapter',
            'bibtex' => 'incollection',
            'ris' => 'CHAP',
            'endnote' => 'Book Section'
        ),
        array(
            'ilib' => 'conference',
            'bibtex' => 'inproceedings',
            'ris' => 'CONF',
            'endnote' => 'Conference Paper'
        ),
        array(
            'ilib' => 'manual',
            'bibtex' => 'manual',
            'ris' => 'STAND',
            'endnote' => 'Standard'
        ),
        array(
            'ilib' => 'thesis',
            'bibtex' => 'phdthesis',
            'ris' => 'THES',
            'endnote' => 'Thesis'
        ),
        array(
            'ilib' => 'patent',
            'bibtex' => 'patent',
            'ris' => 'PAT',
            'endnote' => 'Patent'
        ),
        array(
            'ilib' => 'electronic',
            'bibtex' => 'electronic',
            'ris' => 'ELEC',
            'endnote' => 'Electronic Source'
        ),
        array(
            'ilib' => 'unpublished',
            'bibtex' => 'unpublished',
            'ris' => 'UNPB',
            'endnote' => 'Unpublished Work'
        )
    );
    foreach ($types as $type) {
        if (strtolower($type[$from]) == strtolower($input))
            $output = $type[$to];
    }
    return $output;
}

function cache_name() {
    global $temp_dir;
    $clipboard = array();
    if (isset($_SESSION['session_clipboard']))
        $clipboard = $_SESSION['session_clipboard'];
    if (isset($_SESSION['limit']))
        $clipboard[] = $_SESSION['limit'];
    if (isset($_SESSION['orderby']))
        $clipboard[] = $_SESSION['orderby'];
    if (isset($_SESSION['display']))
        $clipboard[] = $_SESSION['display'];
    $md5_cache_array = array_merge($_POST, $_GET, $clipboard);
    unset($md5_cache_array['_']);
    unset($md5_cache_array['proxystr']);
    ksort($md5_cache_array);
    $md5_cache_string = serialize($md5_cache_array);
    $md5_cache = md5(__FILE__ . $md5_cache_string);
    $cache_name = 'page_' . $md5_cache;
    $cache_name = $temp_dir . DIRECTORY_SEPARATOR . 'lib_' . session_id() . DIRECTORY_SEPARATOR . $cache_name;
    return $cache_name;
}

function database_change() {

    global $database_path;
    $ch_time = 0;
    $ch_time2 = 0;
    $tables = array();
    $tables2 = array();
    $tables_arr = func_get_args();
    if (isset($tables_arr[0]))
        $tables = (array) $tables_arr[0];
    if (isset($tables_arr[1]))
        $tables2 = (array) $tables_arr[1];

    // READ DATABASE MTIME

    if (count($tables) > 0) {
        foreach ($tables as $table) {
            $query_arr[] = "ch_table='" . $table . "'";
        }
        $query_str = join(' OR ', $query_arr);

        $dbHandle = database_connect($database_path, 'library');
        $result = $dbHandle->query("SELECT max(ch_time) FROM library_log
            WHERE " . $query_str);
        $ch_time = $result->fetchColumn();
        $result = null;
        $dbHandle = null;
    }

    if (count($tables2) > 0) {
        foreach ($tables2 as $table) {
            $query_arr[] = "ch_table='" . $table . "'";
        }
        $query_str = join(' OR ', $query_arr);

        $dbHandle = database_connect($database_path, 'fulltext');
        $result = $dbHandle->query("SELECT max(ch_time) FROM fulltext_log
            WHERE " . $query_str);
        $ch_time2 = $result->fetchColumn();
        $result = null;
        $dbHandle = null;
    }

    return max($ch_time, $ch_time2);
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

function cache_clear() {
    global $temp_dir;
    //DELETE CACHED SHELF AND PROJECTS
    @unlink($temp_dir . DIRECTORY_SEPARATOR . 'lib_' . session_id() . DIRECTORY_SEPARATOR . 'shelf_files');
    $clean_files = glob($temp_dir . DIRECTORY_SEPARATOR . 'lib_*' . DIRECTORY_SEPARATOR . 'desk_files', GLOB_NOSORT);
    if (is_array($clean_files)) {
        foreach ($clean_files as $clean_file) {
            if (is_file($clean_file) && is_writable($clean_file))
                @unlink($clean_file);
        }
    }
}

function save_export_files($files) {
    global $temp_dir;
    $filename = $temp_dir . DIRECTORY_SEPARATOR . 'lib_' . session_id() . DIRECTORY_SEPARATOR . 'export_files';
    $export_files = array();
    $export_files['timestamp'] = time();
    $export_files['files'] = $files;
    $export_files_content = serialize($export_files);
    file_put_contents($filename, $export_files_content, LOCK_EX);
}

function read_export_files($ch_time) {

    global $temp_dir;
    $export_files_array['timestamp'] = 0;
    $export_files_array['files'] = null;
    $filename = $temp_dir . DIRECTORY_SEPARATOR . 'lib_' . session_id() . DIRECTORY_SEPARATOR . 'export_files';

    if (is_readable($filename))
        $export_files_array = unserialize(file_get_contents($filename));
    if ($ch_time < $export_files_array['timestamp'])
        return $export_files_array['files'];
}

function graphical_abstract($file) {
    $filename = sprintf("%05d", intval($file));
    $filename_array = glob('library/supplement/' . $filename . 'graphical_abstract.*');
    if (!empty($filename_array[0]))
        return $filename_array[0];
}

function get_username($dbHandle, $database_path, $userID) {
    $dbHandle->exec("ATTACH DATABASE '" . $database_path . "users.sq3' AS usersdatabase");
    $query = $dbHandle->quote($userID);
    $result = $dbHandle->query("SELECT usersdatabase.users.username AS username FROM usersdatabase.users WHERE userID=$query LIMIT 1");
    $username = $result->fetchColumn();
    $dbHandle->exec("DETACH DATABASE usersdatabase");
    return $username;
}

/////////////create, upgrade, or connect to database//////////////////////

function database_connect($database_path, $database_name) {
    global $dbHandle;
    /////////////create databases//////////////////////
    if (!is_file($database_path . 'library.sq3')) {
        try {
            $dbHandle = new PDO('sqlite:' . $database_path . 'library.sq3');
        } catch (PDOException $e) {
            print "Error: " . $e->getMessage() . "<br/>";
            print "PHP extensions PDO and PDO_SQLite must be installed.";
            die();
        }
        $dbHandle->beginTransaction();
        $dbHandle->exec("CREATE TABLE library (
                id integer PRIMARY KEY,
                file text NOT NULL DEFAULT '',
                authors text NOT NULL DEFAULT '',
                affiliation text NOT NULL DEFAULT '',
                title text NOT NULL DEFAULT '',
                journal text NOT NULL DEFAULT '',
                secondary_title text NOT NULL DEFAULT '',
                year text NOT NULL DEFAULT '',
                volume text NOT NULL DEFAULT '',
                issue text NOT NULL DEFAULT '',
                pages text NOT NULL DEFAULT '',
                abstract text NOT NULL DEFAULT '',
                keywords text NOT NULL DEFAULT '',
                editor text NOT NULL DEFAULT '',
                publisher text NOT NULL DEFAULT '',
                place_published text NOT NULL DEFAULT '',
                reference_type text NOT NULL DEFAULT '',
                uid text NOT NULL DEFAULT '',
                doi text NOT NULL DEFAULT '',
                url text NOT NULL DEFAULT '',
                addition_date text NOT NULL DEFAULT '',
                rating integer NOT NULL DEFAULT '',
                authors_ascii text NOT NULL DEFAULT '',
                title_ascii text NOT NULL DEFAULT '',
                abstract_ascii text NOT NULL DEFAULT '',
                added_by integer NOT NULL DEFAULT '',
                modified_by integer NOT NULL DEFAULT '',
                modified_date text NOT NULL DEFAULT '',
                custom1 text NOT NULL DEFAULT '',
                custom2 text NOT NULL DEFAULT '',
                custom3 text NOT NULL DEFAULT '',
                custom4 text NOT NULL DEFAULT '',
                bibtex text NOT NULL DEFAULT '',
                tertiary_title text NOT NULL DEFAULT '',
                filehash text NOT NULL DEFAULT ''
                )");
        $dbHandle->exec("CREATE TABLE shelves (
                fileID integer NOT NULL DEFAULT '',
                userID integer NOT NULL DEFAULT '',
                UNIQUE (fileID,userID)
                )");
        $dbHandle->exec("CREATE TABLE categories (
                categoryID integer PRIMARY KEY,
                category text NOT NULL DEFAULT ''
                )");
        $dbHandle->exec("CREATE TABLE filescategories (
                fileID integer NOT NULL,
                categoryID integer NOT NULL,
                UNIQUE(fileID,categoryID)
		  )");
        $dbHandle->exec("CREATE TABLE projects (
                projectID integer PRIMARY KEY,
                userID integer NOT NULL,
                project text NOT NULL,
                active text NOT NULL
                )");
        $dbHandle->exec("CREATE TABLE projectsfiles (
                projectID integer NOT NULL,
                fileID integer NOT NULL,
                UNIQUE (projectID,fileID)
                )");
        $dbHandle->exec("CREATE TABLE projectsusers (
                projectID integer NOT NULL,
                userID integer NOT NULL,
                UNIQUE (projectID,userID)
                )");
        $dbHandle->exec("CREATE TABLE notes (
                notesID integer PRIMARY KEY,
                userID integer NOT NULL,
                fileID integer NOT NULL,
                notes text NOT NULL DEFAULT ''
                )");
        $dbHandle->exec("CREATE TABLE searches (
                searchID integer PRIMARY KEY,
                userID integer NOT NULL,
                searchname text NOT NULL DEFAULT '',
                searchfield text NOT NULL DEFAULT '',
                searchvalue text NOT NULL DEFAULT ''
                )");
        $dbHandle->exec("CREATE TABLE yellowmarkers (
                id INTEGER PRIMARY KEY,
                userID INTEGER NOT NULL,
                filename TEXT NOT NULL,
                page INTEGER NOT NULL,
                top TEXT NOT NULL,
                left TEXT NOT NULL,
                width TEXT NOT NULL,
                UNIQUE (userID,filename,page,top,left)
                )");
        $dbHandle->exec("CREATE TABLE annotations (
                id INTEGER PRIMARY KEY,
                userID INTEGER NOT NULL,
                filename TEXT NOT NULL,
                page INTEGER NOT NULL,
                top TEXT NOT NULL,
                left TEXT NOT NULL,
                annotation TEXT NOT NULL,
                UNIQUE (userID,filename,page,top,left)
                )");
        $dbHandle->exec("CREATE INDEX journal_ind ON library (journal)");
        $dbHandle->exec("CREATE INDEX secondary_title_ind ON library (secondary_title)");
        $dbHandle->exec("CREATE INDEX addition_date_ind ON library (addition_date)");
        $dbHandle->exec("CREATE TABLE library_log (
                id integer PRIMARY KEY,
                ch_table text NOT NULL DEFAULT '',
                ch_time text NOT NULL DEFAULT ''
                )");
        $tables = array('annotations', 'categories', 'filescategories', 'flagged', 'library', 'notes',
            'projects', 'projectsfiles', 'projectsusers', 'searches', 'shelves', 'yellowmarkers');
        foreach ($tables as $table) {
            $dbHandle->exec("INSERT INTO library_log (ch_table,ch_time)
                            VALUES('" . $table . "',strftime('%s','now'))");
            $dbHandle->exec("CREATE TRIGGER trigger_" . $table . "_delete AFTER DELETE ON " . $table . " 
                            BEGIN
                                UPDATE library_log SET ch_time=strftime('%s','now') WHERE ch_table='" . $table . "';
                            END;");
            $dbHandle->exec("CREATE TRIGGER trigger_" . $table . "_insert AFTER INSERT ON " . $table . " 
                            BEGIN
                                UPDATE library_log SET ch_time=strftime('%s','now') WHERE ch_table='" . $table . "';
                            END;");
            $dbHandle->exec("CREATE TRIGGER trigger_" . $table . "_update AFTER UPDATE ON " . $table . " 
                            BEGIN
                                UPDATE library_log SET ch_time=strftime('%s','now') WHERE ch_table='" . $table . "';
                            END;");
        }
        $dbHandle->commit();
        $dbHandle = null;
        try {
            $dbHandle = new PDO('sqlite:' . $database_path . 'fulltext.sq3');
        } catch (PDOException $e) {
            print "Error: " . $e->getMessage() . "<br/>";
            print "PHP extensions PDO and PDO_SQLite must be installed.";
            die();
        }
        $dbHandle->beginTransaction();
        $dbHandle->exec("CREATE TABLE full_text (
                    id integer PRIMARY KEY,
                    fileID text NOT NULL DEFAULT '',
                    full_text text NOT NULL DEFAULT ''
                    )");
        $dbHandle->exec("CREATE TABLE fulltext_log (
                id integer PRIMARY KEY,
                ch_table text NOT NULL DEFAULT '',
                ch_time text NOT NULL DEFAULT ''
                )");
        $dbHandle->exec("INSERT INTO fulltext_log (ch_table,ch_time)
                        VALUES('full_text',strftime('%s','now'))");
        $dbHandle->exec("CREATE TRIGGER trigger_fulltext_delete AFTER DELETE ON full_text
                        BEGIN
                            UPDATE fulltext_log SET ch_time=strftime('%s','now') WHERE ch_table='full_text';
                        END;");
        $dbHandle->exec("CREATE TRIGGER trigger_fulltext_insert AFTER INSERT ON full_text
                        BEGIN
                            UPDATE fulltext_log SET ch_time=strftime('%s','now') WHERE ch_table='full_text';
                        END;");
        $dbHandle->exec("CREATE TRIGGER trigger_fulltext_update AFTER UPDATE ON full_text
                        BEGIN
                            UPDATE fulltext_log SET ch_time=strftime('%s','now') WHERE ch_table='full_text';
                        END;");
        $dbHandle->commit();
        $dbHandle = null;
        try {
            $dbHandle = new PDO('sqlite:' . $database_path . 'users.sq3');
        } catch (PDOException $e) {
            print "Error: " . $e->getMessage() . "<br/>";
            print "PHP extensions PDO and PDO_SQLite must be installed.";
            die();
        }
        $dbHandle->beginTransaction();
        $dbHandle->exec("CREATE TABLE users (
                userID integer PRIMARY KEY,
                username text UNIQUE NOT NULL DEFAULT '',
                password text NOT NULL DEFAULT '',
                permissions text NOT NULL DEFAULT 'U'
                )");
        $dbHandle->exec("CREATE TABLE settings (
                userID integer NOT NULL DEFAULT '',
                setting_name text NOT NULL DEFAULT '',
                setting_value text NOT NULL DEFAULT ''
                )");
        $dbHandle->commit();
    }
    /////////////connect to database//////////////////////
    try {
        $dbHandle = new PDO('sqlite:' . $database_path . $database_name . '.sq3');
    } catch (PDOException $e) {
        print "Error: " . $e->getMessage() . "<br/>";
        print "PHP extensions PDO and PDO_SQLite must be installed.";
        die();
    }
    //SWITCH TO WAL MODE IF SQLITE >3.7.0, DELETE MODE >3.6.0 <3.7.1
    $result = $dbHandle->query('SELECT sqlite_version()');
    $sqlite_version = $result->fetchColumn();
    $result = null;
    if (version_compare($sqlite_version, "3.5.9", ">")) {
        $journal_mode = 'DELETE';
        if (version_compare($sqlite_version, "3.7.0", ">"))
            $journal_mode = 'WAL';
        $dbHandle->query('PRAGMA journal_mode=' . $journal_mode);
    }
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

    global $pdftotext;

    if (strtoupper(substr(PHP_OS, 0, 3)) == 'WIN' && is_executable('bin' . DIRECTORY_SEPARATOR . 'pdftotext.exe')) {
        $pdftotext = 'bin' . DIRECTORY_SEPARATOR . 'pdftotext.exe -enc UTF-8 ';
    } elseif (PHP_OS == 'Linux') {
        $pdftotext = "pdftotext -enc UTF-8 ";
    } elseif (PHP_OS == 'Darwin' && is_executable(dirname(__FILE__) . DIRECTORY_SEPARATOR . 'bin' . DIRECTORY_SEPARATOR . 'pdftotext.osx')) {
        $pdftotext = dirname(__FILE__) . DIRECTORY_SEPARATOR . 'bin' . DIRECTORY_SEPARATOR . 'pdftotext.osx -enc UTF-8 ';
    }

    return $pdftotext;
}

/////////////select pdfinfo//////////////////////

function select_pdfinfo() {

    global $pdfinfo;

    if (strtoupper(substr(PHP_OS, 0, 3)) == 'WIN' && is_executable('bin' . DIRECTORY_SEPARATOR . 'pdfinfo.exe')) {
        $pdfinfo = 'bin' . DIRECTORY_SEPARATOR . 'pdfinfo.exe ';
    } elseif (PHP_OS == 'Linux') {
        $pdfinfo = "pdfinfo ";
    } elseif (PHP_OS == 'Darwin' && is_executable(dirname(__FILE__) . DIRECTORY_SEPARATOR . 'bin' . DIRECTORY_SEPARATOR . 'pdfinfo.osx')) {
        $pdfinfo = dirname(__FILE__) . DIRECTORY_SEPARATOR . 'bin' . DIRECTORY_SEPARATOR . 'pdfinfo.osx ';
    }

    return $pdfinfo;
}

/////////////select pdftohtml//////////////////////

function select_pdftohtml() {

    global $selected_pdftohtml;

    if (strtoupper(substr(PHP_OS, 0, 3)) == 'WIN' && is_executable('bin' . DIRECTORY_SEPARATOR . 'pdftohtml.exe')) {
        $selected_pdftohtml = 'bin' . DIRECTORY_SEPARATOR . 'pdftohtml.exe';
    } elseif (PHP_OS == 'Linux') {
        $selected_pdftohtml = "pdftohtml";
    } elseif (PHP_OS == 'Darwin' && is_executable(dirname(__FILE__) . DIRECTORY_SEPARATOR . 'bin/poppler/Frameworks' . DIRECTORY_SEPARATOR . 'pdftohtml')) {
        $selected_pdftohtml = dirname(__FILE__) . DIRECTORY_SEPARATOR . 'bin/poppler/Frameworks' . DIRECTORY_SEPARATOR . 'pdftohtml';
    }

    return $selected_pdftohtml;
}

/////////////select ghostscript//////////////////////

function select_ghostscript() {

    global $selected_ghostscript;

    if (strtoupper(substr(PHP_OS, 0, 3)) == 'WIN' && is_executable('bin' . DIRECTORY_SEPARATOR . 'gs' . DIRECTORY_SEPARATOR . 'gswin32c.exe')) {
        $selected_ghostscript = 'bin' . DIRECTORY_SEPARATOR . 'gs' . DIRECTORY_SEPARATOR . 'gswin32c.exe';
    } elseif (PHP_OS == 'Linux') {
        $selected_ghostscript = 'gs';
    } elseif (PHP_OS == 'Darwin' && is_executable(dirname(__FILE__) . DIRECTORY_SEPARATOR . 'bin' . DIRECTORY_SEPARATOR . 'gs' . DIRECTORY_SEPARATOR . 'gs.osx')) {
        $selected_ghostscript = dirname(__FILE__) . DIRECTORY_SEPARATOR . 'bin' . DIRECTORY_SEPARATOR . 'gs' . DIRECTORY_SEPARATOR . 'gs.osx';
    }

    return $selected_ghostscript;
}

/////////////select pdftk//////////////////////

function select_pdftk() {

    global $pdftk;

    if (strtoupper(substr(PHP_OS, 0, 3)) == 'WIN' && is_executable('bin' . DIRECTORY_SEPARATOR . 'pdftk' . DIRECTORY_SEPARATOR . 'pdftk.exe')) {
        $pdftk = 'bin' . DIRECTORY_SEPARATOR . 'pdftk' . DIRECTORY_SEPARATOR . 'pdftk.exe ';
    } elseif (PHP_OS == 'Linux') {
        $pdftk = "pdftk ";
    } elseif (PHP_OS == 'Darwin') {
        $pdftk = '/usr/local/bin/pdftk ';
    }

    return $pdftk;
}

/////////////proxy_file_get_contents//////////////////////

function proxy_file_get_contents($url, $proxy_name, $proxy_port, $proxy_username, $proxy_password) {

    global $pdf, $csv, $ris;
    $pdf_string = '';
    if (isset($proxy_name) && !empty($proxy_name)) {

        $proxy_fp = @fsockopen($proxy_name, $proxy_port);

        if ($proxy_fp) {

            $pdf_string = '';
            $cookies = array();

            fputs($proxy_fp, "GET $url HTTP/1.0\r\nHost: $proxy_name\r\n");
            if (!empty($proxy_username))
                fputs($proxy_fp, "Proxy-Authorization: Basic " . base64_encode("$proxy_username:$proxy_password") . "\r\n");
            fputs($proxy_fp, "User-Agent: \"$_SERVER[HTTP_USER_AGENT]\"\r\n\r\n");

            while (!feof($proxy_fp)) {
                $pdf_string .= fgets($proxy_fp, 128);
            }

            fclose($proxy_fp);

            $pdf = strstr($pdf_string, "%PDF");
            $csv = strstr($pdf_string, "Item Title");
            $ris = strstr($pdf_string, "TY  -");

            if (empty($pdf)) {

                $response = array();
                $response = explode("\r\n", $pdf_string);

                while (list($key, $value) = each($response)) {

                    if (stripos($value, "Location: ") === 0) {
                        if ($value != $url)
                            $new_url = trim(substr($value, 10));
                    }

                    if (stripos($value, "Set-Cookie: ") === 0) {
                        $cookies[] = trim($value);
                    }
                }

                if (!empty($new_url)) {

                    $pdf_string = '';

                    if (stripos($new_url, "http") !== 0)
                        $new_url = parse_url($url, PHP_URL_SCHEME) . '://' . parse_url($url, PHP_URL_HOST) . $new_url;

                    $proxy_fp = @fsockopen($proxy_name, $proxy_port);

                    fputs($proxy_fp, "GET $new_url HTTP/1.0\r\nHost: $proxy_name\r\n");
                    foreach ($cookies as $cookie) {
                        if (!empty($cookie))
                            fputs($proxy_fp, "Cookie: " . substr($cookie, 12) . "\r\n");
                    }
                    if (!empty($proxy_username))
                        fputs($proxy_fp, "Proxy-Authorization: Basic " . base64_encode("$proxy_username:$proxy_password") . "\r\n");
                    fputs($proxy_fp, "User-Agent: \"$_SERVER[HTTP_USER_AGENT]\"\r\n\r\n");

                    while (!feof($proxy_fp)) {
                        $pdf_string .= fgets($proxy_fp, 128);
                    }
                    fclose($proxy_fp);

                    $pdf = strstr($pdf_string, "%PDF");

                    if (empty($pdf)) {

                        $response = array();
                        $response = explode("\r\n", $pdf_string);

                        while (list($key, $value) = each($response)) {

                            if (stripos($value, "Location: ") === 0) {
                                if ($value != $url)
                                    $new_url = trim(substr($value, 10));
                            }
                            if (stripos($value, "Set-Cookie: ") === 0) {
                                $cookies[] = trim($value);
                            }
                        }

                        if (!empty($new_url)) {

                            $pdf_string = '';

                            if (stripos($new_url, "http") !== 0)
                                $new_url = parse_url($url, PHP_URL_SCHEME) . '://' . parse_url($url, PHP_URL_HOST) . $new_url;

                            $proxy_fp = @fsockopen($proxy_name, $proxy_port);

                            fputs($proxy_fp, "GET $new_url HTTP/1.0\r\nHost: $proxy_name\r\n");
                            foreach ($cookies as $cookie) {
                                if (!empty($cookie))
                                    fputs($proxy_fp, "Cookie: " . substr($cookie, 12) . "\r\n");
                            }
                            if (!empty($proxy_username))
                                fputs($proxy_fp, "Proxy-Authorization: Basic " . base64_encode("$proxy_username:$proxy_password") . "\r\n");
                            fputs($proxy_fp, "User-Agent: \"$_SERVER[HTTP_USER_AGENT]\"\r\n\r\n");

                            while (!feof($proxy_fp)) {
                                $pdf_string .= fgets($proxy_fp, 128);
                            }

                            fclose($proxy_fp);

                            $pdf = strstr($pdf_string, "%PDF");
                        }
                    }
                }
            }
        }
    } else {

        $host = parse_url($url, PHP_URL_HOST);
        $path = parse_url($url, PHP_URL_PATH);
        $query = parse_url($url, PHP_URL_QUERY);

        $proxy_fp = @fsockopen($host, 80);

        if ($proxy_fp) {

            $pdf_string = '';
            $cookies = array();

            fputs($proxy_fp, "GET $path?$query HTTP/1.0\r\n");
            fputs($proxy_fp, "Host: $host\r\n");
            fputs($proxy_fp, "User-Agent: \"$_SERVER[HTTP_USER_AGENT]\"\r\n\r\n");

            while (!feof($proxy_fp)) {
                $pdf_string .= fgets($proxy_fp, 128);
            }

            fclose($proxy_fp);

            $pdf = strstr($pdf_string, "%PDF");
            $csv = strstr($pdf_string, "Item Title");
            $ris = strstr($pdf_string, "TY  -");

            if (empty($pdf)) {

                $response = array();
                $response = explode("\r\n", $pdf_string);

                while (list($key, $value) = each($response)) {

                    if (stripos($value, "Location: ") === 0) {
                        if ($value != $url)
                            $new_url = trim(substr($value, 10));
                    }
                    if (stripos($value, "Set-Cookie: ") === 0) {
                        $cookies[] = trim($value);
                    }
                }

                if (!empty($new_url)) {

                    $pdf_string = '';

                    if (stripos($new_url, "http") !== 0)
                        $new_url = parse_url($url, PHP_URL_SCHEME) . '://' . parse_url($url, PHP_URL_HOST) . $new_url;

                    $host = parse_url($new_url, PHP_URL_HOST);
                    $path = parse_url($new_url, PHP_URL_PATH);
                    $query = parse_url($new_url, PHP_URL_QUERY);

                    $proxy_fp = @fsockopen($host, 80);

                    fputs($proxy_fp, "GET $path?$query HTTP/1.0\r\n");
                    fputs($proxy_fp, "Host: $host\r\n");
                    foreach ($cookies as $cookie) {
                        if (!empty($cookie))
                            fputs($proxy_fp, "Cookie: " . substr($cookie, 12) . "\r\n");
                    }
                    fputs($proxy_fp, "User-Agent: \"$_SERVER[HTTP_USER_AGENT]\"\r\n\r\n");

                    while (!feof($proxy_fp)) {
                        $pdf_string .= fgets($proxy_fp, 128);
                    }

                    fclose($proxy_fp);

                    $pdf = strstr($pdf_string, "%PDF");

                    if (empty($pdf)) {

                        $response = array();
                        $response = explode("\r\n", $pdf_string);

                        while (list($key, $value) = each($response)) {

                            if (stripos($value, "Location: ") === 0) {
                                if ($value != $url)
                                    $new_url = trim(substr($value, 10));
                            }
                            if (stripos($value, "Set-Cookie: ") === 0) {
                                $cookies[] = trim($value);
                            }
                        }

                        if (!empty($new_url)) {

                            $pdf_string = '';

                            if (stripos($new_url, "http") !== 0)
                                $new_url = parse_url($url, PHP_URL_SCHEME) . '://' . parse_url($url, PHP_URL_HOST) . $new_url;

                            $host = parse_url($new_url, PHP_URL_HOST);
                            $path = parse_url($new_url, PHP_URL_PATH);
                            $query = parse_url($new_url, PHP_URL_QUERY);

                            $proxy_fp = @fsockopen($host, 80);

                            fputs($proxy_fp, "GET $path?$query HTTP/1.0\r\n");
                            fputs($proxy_fp, "Host: $host\r\n");
                            foreach ($cookies as $cookie) {
                                if (!empty($cookie))
                                    fputs($proxy_fp, "Cookie: " . substr($cookie, 12) . "\r\n");
                            }
                            fputs($proxy_fp, "User-Agent: \"$_SERVER[HTTP_USER_AGENT]\"\r\n\r\n");

                            while (!feof($proxy_fp)) {
                                $pdf_string .= fgets($proxy_fp, 128);
                            }

                            fclose($proxy_fp);

                            $pdf = strstr($pdf_string, "%PDF");
                        }
                    }
                }
            }
        }
    }
    if (!empty($pdf))
        return $pdf;
    if (!empty($csv))
        return $csv;
    if (!empty($ris))
        return $ris;
}

/////////////proxy_simplexml_load_file//////////////////////

function proxy_simplexml_load_file($url, $proxy_name, $proxy_port, $proxy_username, $proxy_password) {

    global $xml;
    $xml = false;
    $xml_string = '';
    $xml_string2 = '';

    if (isset($proxy_name) && !empty($proxy_name)) {

        $proxy_fp = @fsockopen($proxy_name, $proxy_port, $errno, $errstr, 10);

        if ($proxy_fp) {

            fputs($proxy_fp, "GET $url HTTP/1.0\r\nHost: $proxy_name\r\n");
            if (!empty($proxy_username))
                fputs($proxy_fp, "Proxy-Authorization: Basic " . base64_encode("$proxy_username:$proxy_password") . "\r\n");
            fputs($proxy_fp, "User-Agent: \"$_SERVER[HTTP_USER_AGENT]\"\r\n\r\n");

            while (!feof($proxy_fp)) {
                $xml_string2 .= fgets($proxy_fp, 128);
            }

            fclose($proxy_fp);

            $xml_string = strstr($xml_string2, "<?xml");
            $xml = simplexml_load_string($xml_string);
            #JSTOR hack
            if (empty($xml) && strpos($url, 'jstor') !== false) {
                $xml = new XMLReader();
                $xml->xml($xml_string);
            }
            #NASA PHYS hack
            if (empty($xml) && strpos($url, 'adsabs') !== false) {

                $response = array();
                $response = explode("\r\n", $xml_string2);

                while (list($key, $value) = each($response)) {

                    if (stripos($value, "Location: ") === 0) {
                        $new_url = trim(substr($value, 10));
                        if ($new_url != $url)
                            break;
                    }
                }

                if (!empty($new_url)) {

                    $xml_string = '';

                    if (stripos($new_url, "http") !== 0)
                        $new_url = parse_url($url, PHP_URL_SCHEME) . '://' . parse_url($url, PHP_URL_HOST) . $new_url;

                    $proxy_fp = @fsockopen($proxy_name, $proxy_port);

                    fputs($proxy_fp, "GET $new_url HTTP/1.0\r\nHost: $proxy_name\r\n");
                    if (!empty($proxy_username))
                        fputs($proxy_fp, "Proxy-Authorization: Basic " . base64_encode("$proxy_username:$proxy_password") . "\r\n");
                    fputs($proxy_fp, "User-Agent: \"$_SERVER[HTTP_USER_AGENT]\"\r\n\r\n");

                    while (!feof($proxy_fp)) {
                        $xml_string .= fgets($proxy_fp, 128);
                    }

                    fclose($proxy_fp);

                    $xml_string = strstr($xml_string, "<?xml");
                    $xml = simplexml_load_string($xml_string);
                }
            }
        }
    } else {

        ini_set('user_agent', $_SERVER['HTTP_USER_AGENT']);
        $xml = @simplexml_load_file($url);

        #JSTOR hack
        if (strpos($url, 'jstor') !== false) {
            $xml = new XMLReader();
            $xml->open($url);
        }
        #NASA PHYS hack
        if (empty($xml) && strpos($url, 'adsabs') !== false) {
            $xml_string2 = '';
            $host = parse_url($url, PHP_URL_HOST);
            $path = parse_url($url, PHP_URL_PATH);
            $query = parse_url($url, PHP_URL_QUERY);

            $proxy_fp = @fsockopen($host, 80);

            if ($proxy_fp) {

                fputs($proxy_fp, "GET $path?$query HTTP/1.0\r\n");
                fputs($proxy_fp, "Host: $host\r\n");
                fputs($proxy_fp, "User-Agent: \"$_SERVER[HTTP_USER_AGENT]\"\r\n\r\n");

                while (!feof($proxy_fp)) {
                    $xml_string2 .= fgets($proxy_fp, 128);
                }

                fclose($proxy_fp);

                $response = array();
                $response = explode("\r\n", $xml_string2);

                while (list($key, $value) = each($response)) {

                    if (stripos($value, "Location: ") === 0) {
                        $new_url = trim(substr($value, 10));
                        if ($new_url != $url)
                            break;
                    }
                }

                if (!empty($new_url)) {

                    if (stripos($new_url, "http") !== 0)
                        $new_url = parse_url($url, PHP_URL_SCHEME) . '://' . parse_url($url, PHP_URL_HOST) . $new_url;

                    ini_set('user_agent', $_SERVER['HTTP_USER_AGENT']);
                    $xml = @simplexml_load_file($url);
                }
            }
        }
    }
//    $xml = false;
    return $xml;
}

function proxy_dom_load_file($url, $proxy_name, $proxy_port, $proxy_username, $proxy_password) {

    global $dom;
    $dom = false;
    $context = null;

    if (isset($proxy_name) && !empty($proxy_name)) {

        $context = array
            (
            'http' => array
                (
                'proxy' => $proxy_name . ':' . $proxy_port,
                'request_fulluri' => true,
                'header' => "Proxy-Authorization: Basic " . base64_encode("$proxy_username:$proxy_password")
            )
        );

        $context = stream_context_create($context);
    }
    $dom = @file_get_contents($url, false, $context);
    if ($dom === false)
        $dom = '';
    return $dom;
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

        $bibcode = $record->bibcode;
        $response['title'] = $record->title;

        $journal = $record->journal;
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

        $response['volume'] = $record->volume;
        $response['pages'] = $record->page;
        $last_page = $record->lastpage;
        if (!empty($last_page))
            $response['pages'] = $response['pages'] . '-' . $last_page;

        $response['affiliation'] = $record->affiliation;

        $year = $record->pubdate;
        $response['year'] = date('Y-m-d', strtotime($year));

        $response['abstract'] = (string) $record->abstract;
        if ($response['abstract'] == 'Not Available')
            unset($response['abstract']);
        $nasa_url = $record->url;

        foreach ($record->link as $links) {

            foreach ($links->attributes() as $a => $b) {

                if ($a == 'type' && $b == 'EJOURNAL') {
                    $ejournal_url = $links->url;
                } elseif ($a == 'type' && $b == 'PREPRINT') {
                    $preprint_url = $links->url;
                } elseif ($a == 'type' && $b == 'GIF') {
                    $gif_url = $links->url;
                }
            }
        }

        $authors = $record->author;

        $name_array = array();

        if (!empty($authors)) {

            foreach ($authors as $author) {
                $author_array = explode(",", $author);
                $name_array[] = 'L:"' . trim($author_array[0]) . '",F:"' . trim($author_array[1]) . '"';
            }
        }

        if (isset($name_array))
            $response['authors'] = join(";", $name_array);

        $keywords = $record->keywords;

        if (!empty($keywords)) {

            foreach ($keywords as $keyword) {

                $keywords_array[] = $keyword->keyword;
            }
        }

        if (isset($keywords_array))
            $response['keywords'] = join(" / ", $keywords_array);

        if (!empty($bibcode))
            $response['uid'][] = "NASAADS:$bibcode";
        if (!empty($eprintid))
            $response['uid'][] = "ARXIV:$eprintid";

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
        $contributors = $record->contributors->contributor;
        if (count($contributors) > 0) {
            foreach ($contributors as $contributor) {

                $authors1 = html_entity_decode($contributor->surname);
                $authors2 = html_entity_decode($contributor->given_name);
                $authors[] = 'L:"' . $authors1 . '",F:"' . $authors2 . '"';
            }
        }
        if (count($authors) > 0)
            $response['authors'] = join(";", $authors);
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
        $response['secondary_title'] = $children->journal_ref;

        $response['doi'] = $children->doi;

        $pub_date = $record->published;
        $response['year'] = date("Y-m-d", strtotime($pub_date));

        $response['abstract'] = trim($record->summary);

        $authors = $record->author;

        $name_array = array();
        if (!empty($authors)) {

            foreach ($authors as $author) {

                $author = $author->name;
                $author_array = explode(' ', $author);
                $last = array_pop($author_array);
                $first = join(' ', $author_array);
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

        $request_url = "http://www.ncbi.nlm.nih.gov/entrez/eutils/esearch.fcgi?db=Pubmed&term=" . $doi . "[AID]&usehistory=y&retstart=&retmax=1&sort=&tool=I,Librarian&email=i.librarian.software@gmail.com";

        $xml = proxy_simplexml_load_file($request_url, $proxy_name, $proxy_port, $proxy_username, $proxy_password);
        if (empty($xml))
            die('Error! I, Librarian could not connect with an external web service. This usually indicates that you access the Web through a proxy server.
            Enter your proxy details in Tools->Settings. Alternatively, the external service may be temporarily down. Try again later.');

        $count = $xml->Count;
        if ($count == 1)
            $pmid = $xml->IdList->Id;
    }

    if (!empty($pmid)) {

        ##########	open efetch, read xml	##########

        $request_url = "http://www.ncbi.nlm.nih.gov/entrez/eutils/efetch.fcgi?db=Pubmed&rettype=abstract&retmode=XML&id=" . urlencode($pmid) . "&tool=I,Librarian&email=i.librarian.software@gmail.com";

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

            $response['url'][] = "http://www.pubmed.org/$pmid";

            $response['reference_type'] = 'article';

            $response['title'] = $xml->PubmedArticle->MedlineCitation->Article->ArticleTitle;

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

            $response['secondary_title'] = $xml->PubmedArticle->MedlineCitation->Article->Journal->Title;

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

            $response['volume'] = $xml->PubmedArticle->MedlineCitation->Article->Journal->JournalIssue->Volume;

            $response['issue'] = $xml->PubmedArticle->MedlineCitation->Article->Journal->JournalIssue->Issue;

            $response['pages'] = $xml->PubmedArticle->MedlineCitation->Article->Pagination->MedlinePgn;

            $response['journal_abbr'] = $xml->PubmedArticle->MedlineCitation->MedlineJournalInfo->MedlineTA;

            $authors = $xml->PubmedArticle->MedlineCitation->Article->AuthorList->Author;

            $name_array = array();
            $response['affiliation'] = '';
            if (!empty($authors)) {
                foreach ($authors as $author) {
                    $name_array[] = 'L:"' . $author->LastName . '",F:"' . $author->ForeName . '"';
                    if (empty($response['affiliation']))
                        $response['affiliation'] = $author->Affiliation;
                }
            }

            $mesh = $xml->PubmedArticle->MedlineCitation->MeshHeadingList->MeshHeading;

            if (!empty($mesh)) {
                foreach ($mesh as $meshheading) {
                    $mesh_array[] = $meshheading->DescriptorName;
                }
            }

            if (isset($name_array))
                $response['authors'] = join(";", $name_array);
            if (isset($mesh_array))
                $response['keywords'] = join(" / ", $mesh_array);
        }

        if ($xml_type == 'book') {

            $pmid = $xml->PubmedBookArticle->BookDocument->PMID;

            $response['uid'][] = "PMID:$pmid";

            $response['url'][] = "http://www.pubmed.org/$pmid";

            $response['title'] = $xml->PubmedBookArticle->BookDocument->ArticleTitle;

            if (empty($response['title'])) {
                $response['reference_type'] = 'book';
                $response['title'] = $xml->PubmedBookArticle->BookDocument->Book->BookTitle;
                $response['secondary_title'] = $xml->PubmedBookArticle->BookDocument->Book->CollectionTitle;
            } else {
                $response['reference_type'] = 'chapter';
                $response['secondary_title'] = $xml->PubmedBookArticle->BookDocument->Book->BookTitle;
                $response['tertiary_title'] = $xml->PubmedBookArticle->BookDocument->Book->CollectionTitle;
            }

            $response['publisher'] = $xml->PubmedBookArticle->BookDocument->Book->Publisher->PublisherName;
            $response['place_published'] = $xml->PubmedBookArticle->BookDocument->Book->Publisher->PublisherLocation;

            $abstract_array = array();

            foreach ($xml->PubmedBookArticle->BookDocument->Abstract->AbstractText as $mini_ab) {

                foreach ($mini_ab->attributes() as $a => $b) {
                    if ($a == 'Label')
                        $mini_ab = $b . ": " . $mini_ab;
                }
                $abstract_array[] = "$mini_ab";
            }
            $response['abstract'] = implode(' ', $abstract_array);

            $day = $xml->PubmedBookArticle->BookDocument->Book->PubDate->Day;
            $month = $xml->PubmedBookArticle->BookDocument->Book->PubDate->Month;
            $year = $xml->PubmedBookArticle->BookDocument->Book->PubDate->Year;

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
                    $name_array[] = 'L:"' . $author->LastName . '",F:"' . $author->ForeName . '"';
                }
            }
            if (isset($name_array))
                $response['authors'] = join(";", $name_array);

            $editors = $xml->PubmedBookArticle->BookDocument->Book->AuthorList->Author;

            $name_array = array();
            if (!empty($editors)) {
                foreach ($editors as $editor) {
                    $name_array[] = 'L:"' . $editor->LastName . '",F:"' . $editor->ForeName . '"';
                }
            }
            if (isset($name_array))
                $response['editors'] = join(";", $name_array);
        }
    }
}

function record_unknown($dbHandle, $title, $string, $file, $userID) {

    global $temp_dir, $database_path;
    $query = "INSERT INTO library (file, title, title_ascii, addition_date, rating, added_by)
             VALUES ((SELECT IFNULL((SELECT SUBSTR('0000' || CAST(MAX(file)+1 AS TEXT) || '.pdf',-9,9) FROM library),'00001.pdf')), :title, :title_ascii, :addition_date, :rating, :added_by)";

    $stmt = $dbHandle->prepare($query);

    $stmt->bindParam(':title', $title, PDO::PARAM_STR);
    $stmt->bindParam(':title_ascii', $title_ascii, PDO::PARAM_STR);
    $stmt->bindParam(':addition_date', $addition_date, PDO::PARAM_STR);
    $stmt->bindParam(':rating', $rating, PDO::PARAM_INT);
    $stmt->bindParam(':added_by', $added_by, PDO::PARAM_INT);

    if (empty($title))
        $title = basename($file);
    $title_ascii = utf8_deaccent($title);
    $addition_date = date('Y-m-d');
    $rating = 2;
    $added_by = intval($userID);

    $dbHandle->exec("BEGIN IMMEDIATE TRANSACTION");

    $stmt->execute();
    $stmt = null;

    $last_insert = $dbHandle->query("SELECT last_insert_rowid(),max(file) FROM library");
    $last_row = $last_insert->fetch(PDO::FETCH_ASSOC);
    $last_insert = null;
    $id = $last_row['last_insert_rowid()'];
    $new_file = $last_row['max(file)'];

    if (isset($_GET['shelf']) && !empty($userID)) {
        $user_query = $dbHandle->quote($userID);
        $file_query = $dbHandle->quote($id);
        $dbHandle->exec("INSERT OR IGNORE INTO shelves (userID,fileID) VALUES ($user_query,$file_query)");
        @unlink($temp_dir . DIRECTORY_SEPARATOR . 'lib_' . session_id() . DIRECTORY_SEPARATOR . 'shelf_files');
    }

    if (isset($_GET['project']) && !empty($_GET['projectID'])) {
        $dbHandle->exec("INSERT OR IGNORE INTO projectsfiles (projectID,fileID) VALUES (" . intval($_GET['projectID']) . "," . intval($id) . ")");
        $clean_files = glob($temp_dir . DIRECTORY_SEPARATOR . 'lib_*' . DIRECTORY_SEPARATOR . 'desk_files', GLOB_NOSORT);
        if (is_array($clean_files)) {
            foreach ($clean_files as $clean_file) {
                if (is_file($clean_file) && is_writable($clean_file))
                    @unlink($clean_file);
            }
        }
    }

    ####### record new category into categories, if not exists #########

    if (isset($_GET['category2']))
        $category2 = $_GET['category2'];
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
            $last_id = $dbHandle->query("SELECT last_insert_rowid() FROM categories");
            $category_ids[] = $last_id->fetchColumn();
            $last_id = null;
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
        if (!empty($id))
            $stmt->execute();
    }
    $stmt = null;

    $dbHandle->exec("COMMIT");

    copy($file, dirname(__FILE__) . DIRECTORY_SEPARATOR . "library" . DIRECTORY_SEPARATOR . $new_file);

    $hash = md5_file(dirname(__FILE__) . DIRECTORY_SEPARATOR . "library" . DIRECTORY_SEPARATOR . $new_file);

    //RECORD FILE HASH FOR DUPLICATE DETECTION
    if (!empty($hash)) {
        $hash = $dbHandle->quote($hash);
        $dbHandle->exec('UPDATE library SET filehash=' . $hash . ' WHERE id=' . $id);
    }

    $dbHandle = null;

    if (!empty($string)) {

        $dbHandle2 = database_connect($database_path, 'fulltext');

        $file_query = $dbHandle2->quote($id);
        $fulltext_query = $dbHandle2->quote($string);

        $dbHandle2->query("DELETE FROM full_text WHERE fileID=$file_query");
        $insert = $dbHandle2->exec("INSERT INTO full_text (fileID,full_text) VALUES ($file_query,$fulltext_query)");

        $dbHandle2 = null;
    }

    $pdftk = select_pdftk();
    $unpack_dir = $temp_dir . DIRECTORY_SEPARATOR . $new_file;
    @mkdir($unpack_dir);
    exec($pdftk . '"' . dirname(__FILE__) . DIRECTORY_SEPARATOR . 'library' . DIRECTORY_SEPARATOR . $new_file . '" unpack_files output "' . $unpack_dir . '"');
    $unpacked_files = array();
    $unpacked_files = scandir($unpack_dir);
    foreach ($unpacked_files as $unpacked_file) {
        if (is_file($unpack_dir . DIRECTORY_SEPARATOR . $unpacked_file))
            @rename($unpack_dir . DIRECTORY_SEPARATOR . $unpacked_file, dirname(__FILE__) . DIRECTORY_SEPARATOR . 'library' . DIRECTORY_SEPARATOR . 'supplement' . DIRECTORY_SEPARATOR . sprintf("%05d", intval($new_file)) . $unpacked_file);
    }
    @rmdir($unpack_dir);
}

/////////////show results//////////////////////

function show_search_results($result, $select, $display, $shelf_files, $desktop_projects, $tempdbHandle) {

    $project = '';
    if (!empty($_GET['project']))
        $project = $_GET['project'];

    $i = 0;

    if ($display == 'icons')
        print '<table cellspacing=0 id="icon-container" style="border:0;width:100%">
        <tr><td class="alternating_row" style="width:100%;border-bottom:1px #c5c6c8 solid;border-top:1px #c5c6c8 solid;padding-bottom:11px">';

    while (list($key, $paper) = each($result)) {

        $pmid_url = '';
        $pmcid_url = '';
        $nasaads_url = '';
        $arxiv_url = '';
        $jstor_url = '';
        $other_urls = '';
        $urls = '';
        $other_urls = '';
        $uids = array();
        $pmid = '';
        $pmid_related_url = '';
        $pmid_citedby_pmc = '';
        $nasaid = '';
        $nasa_related_url = '';
        $nasa_citedby_pmc = '';
        $ieeeid = '';

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

                if (preg_match('/pubmed\.org/', $url)) {

                    $pmid_url = $url;
                } elseif (preg_match('/pubmedcentral\.nih\.gov/', $url) || preg_match('/\/pmc\//', $url)) {

                    $pmcid_url = $url;
                } elseif (preg_match('/adsabs\.harvard\.edu/', $url)) {

                    $nasaads_url = $url;
                } elseif (preg_match('/arxiv\.org/', $url)) {

                    $arxiv_url = $url;
                } elseif (preg_match('/jstor\.org/', $url)) {

                    $jstor_url = $url;
                } else {

                    $other_urls[] = $url;
                }
            }
        }

        if (!empty($pmid)) {
            $pmid_related_url = 'http://www.ncbi.nlm.nih.gov/sites/entrez?db=pubmed&cmd=link&linkname=pubmed_pubmed&uid=' . $pmid;
            $pmid_citedby_pmc = 'http://www.ncbi.nlm.nih.gov/pubmed?db=pubmed&cmd=link&linkname=pubmed_pubmed_citedin&uid=' . $pmid;
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
                    $first = trim($array2[1]);
                    $first = substr($array2[1], 3, -1);
                    $new_authors[] = '<a href="display.php?select=' . $select . '&browse[' . urlencode($last . ', ' . $first) . ']=authors" class="navigation">'
                            . htmlspecialchars($last . ', ' . $first) . '</a>';
                }
                $paper['authors'] = join('; ', $new_authors);
            }
        }

        $paper['journal'] = htmlspecialchars($paper['journal']);
        $paper['title'] = lib_htmlspecialchars($paper['title']);
        $paper['abstract'] = lib_htmlspecialchars($paper['abstract']);
        $paper['year'] = htmlspecialchars($paper['year']);

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

        $result2 = $tempdbHandle->query("SELECT notesID,notes FROM temp_notes WHERE fileID=" . intval($paper['id']) . " LIMIT 1");
        $fetched = $result2->fetch(PDO::FETCH_ASSOC);
        $result2 = null;

        $paper['notesID'] = $fetched['notesID'];
        $paper['notes'] = $fetched['notes'];

        $i = $i + 1;

        if ($display == 'icons') {

            if (!extension_loaded('gd'))
                die('<p>&nbsp;Error! Icon view requires GD extension and Ghostscript.</p>');

            $first_author = '&nbsp;';
            $auth_arr = explode(';', $paper['authors']);
            $auth_arr2 = explode(',', strip_tags($auth_arr[0]));
            if (!empty($auth_arr2[0]))
                $first_author = $auth_arr2[0];
            $etal = '';
            if (count($auth_arr) > 1)
                $etal = ', et al.';

            print '<div class="thumb-items" id="display-item-' . $paper['id'] . '" data-file="' . $paper['file'] . '"><div>';

            print '<div class="thumb-titles"><div style="overflow:hidden;white-space:nowrap"><b>' . $paper['title'] . '</b><br>' . $first_author . $etal;
            if (!empty($paper['year']))
                print ' (' . substr($paper['year'], 0, 4) . ')';
            print '</div></div>';

            if (date('Y-m-d') == $paper['addition_date'])
                print '<div class="new-item ui-state-error-text">New!</div>';

            if (is_readable('library/' . $paper['file'])) {

                if (isset($_SESSION['pdfviewer']) && $_SESSION['pdfviewer'] == 'external')
                    print '<a href="' . htmlspecialchars('downloadpdf.php?file=' . urlencode($paper['file']) . '#pagemode=none&scrollbar=1&navpanes=0&toolbar=1&statusbar=0&page=1&view=FitH,0') . '" target="_blank" style="display:block">';

                if (!isset($_SESSION['pdfviewer']) || (isset($_SESSION['pdfviewer']) && $_SESSION['pdfviewer'] == 'internal'))
                    print '<a href="' . htmlspecialchars('viewpdf.php?file=' . urlencode($paper['file']) . '&title=' . urlencode($paper['title'])) . '" target="_blank" style="width:360px;height:240px;display:block">';

                print '<img src="icon.php?file=' . $paper['file'] . '" style="width:360px;height:240px;border:0" alt="Loading PDF..."></a>';
            } else {
                print '<div style="margin-top:90px;margin-left:150px;font-size:18px;color:#b5b6b8">No PDF</div>';
            }

            print '</div>';

            print PHP_EOL . '<table class="item-sticker" style="margin:6px 0;width:100%"><tr><td class="noprint ui-corner-all" style="padding:5px 8px;border:1px solid #c5c6c8">';

            print '<i class="fa fa-plus-circle expander view-icon" style="margin-right:0.5em"></i>';

            print '<span><i class="star ' . (($paper['rating'] >= 1) ? 'ui-state-error-text' : 'ui-priority-secondary') . ' fa fa-star"></i>';
            print '&nbsp;<i class="star ' . (($paper['rating'] >= 2) ? 'ui-state-error-text' : 'ui-priority-secondary') . ' fa fa-star"></i>';
            print '&nbsp;<i class="star ' . (($paper['rating'] == 3) ? 'ui-state-error-text' : 'ui-priority-secondary') . ' fa fa-star"></i></span>&nbsp;';

            print '<b style="margin:0 0.5em">&middot;</b>';

            if (isset($shelf_files) && in_array($paper['id'], $shelf_files)) {
                print ' <span class="update_shelf clicked"><i class="update_shelf fa fa-check-square ui-state-error-text"></i>&nbsp;Shelf&nbsp;</span>';
            } else {
                print ' <span class="update_shelf"><i class="update_shelf fa fa-square-o"></i>&nbsp;Shelf&nbsp;</span>';
            }

            if (isset($_SESSION['session_clipboard']) && in_array($paper['id'], $_SESSION['session_clipboard'])) {
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

            print PHP_EOL . '<div id="display-item-' . $paper['id'] . '" class="items" data-file="' . $paper['file'] . '" style="padding:0 0 10px 0">';

            include('coins.php');

            print '<div class="ui-widget-header" style="overflow:hidden;border-left:0;border-right:0;padding:2px 6px">';

            if (is_file('library/' . $paper['file']) && isset($_SESSION['auth'])) {

                if (isset($_SESSION['pdfviewer']) && $_SESSION['pdfviewer'] == 'external')
                    print '<div class="noprint titles-pdf" style="float:left;text-shadow:1px 1px 1px white">
                        <a class="ui-state-error-text" href="' . htmlspecialchars('downloadpdf.php?file=' . urlencode($paper['file']) . '#pagemode=none&scrollbar=1&navpanes=0&toolbar=1&statusbar=0&page=1&view=FitH,0') . '" target="_blank" style="display:block">
                                PDF</a></div>';

                if (!isset($_SESSION['pdfviewer']) || (isset($_SESSION['pdfviewer']) && $_SESSION['pdfviewer'] == 'internal'))
                    print '<div class="noprint titles-pdf" style="float:left;text-shadow:1px 1px 1px white">
                        <a class="ui-state-error-text" href="' . htmlspecialchars('viewpdf.php?file=' . urlencode($paper['file']) . '&title=' . urlencode($paper['title'])) . '" target="_blank" style="display:block">
                                PDF</a></div>';
            } else {
                print PHP_EOL . '<div class="ui-state-error-text noprint titles-pdf" style="float:left;color:#c5c6c8;cursor:auto">PDF</div>';
            }

            print PHP_EOL . '<div class="titles brief">' . $paper['title'] . '</div>';

            print '</div>';

            print '<div style="clear:both"></div>';

            print '<table class="noprint"><tr><td style="width:24px;padding-top:8px;padding-left:12px">';

            if ($display != 'abstract')
                print PHP_EOL . '<i class="expander fa fa-plus-circle view-' . $display . '"></i>';

            print '</td><td style="padding-top:8px">';

            if (isset($shelf_files) && in_array($paper['id'], $shelf_files)) {
                print '<span class="update_shelf clicked"><i class="update_shelf fa fa-check-square ui-state-error-text"></i>&nbsp;Shelf&nbsp;</span>';
            } else {
                print '<span class="update_shelf"><i class="update_shelf fa fa-square-o"></i>&nbsp;Shelf&nbsp;</span>';
            }

            if (isset($_SESSION['session_clipboard']) && in_array($paper['id'], $_SESSION['session_clipboard'])) {
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

            print PHP_EOL . '</td></tr></table>';

            print PHP_EOL . '<div class="display-summary" style="margin:0 30px;';

            print ($display == 'brief') ? 'display:none">' : '">';

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

            if (!empty($paper['bibtex'])) {
                print htmlspecialchars($paper['bibtex']);
            } else {
                $bibtex_author = strip_tags($paper['authors']);
                $bibtex_author = substr($bibtex_author, 0, strpos($bibtex_author, ','));
                if (empty($bibtex_author))
                    $bibtex_author = 'unknown';

                $bibtex_year = '0000';
                $bibtex_year_array = explode('-', $paper['year']);
                if (!empty($bibtex_year_array[0]))
                    $bibtex_year = $bibtex_year_array[0];

                $bibtex_key = utf8_deaccent($bibtex_author) . '-' . $bibtex_year . '-ID' . $paper['id'];
                print htmlspecialchars($bibtex_key);
            }

            print '<b style="margin:0 0.5em">&middot;</b>';

            print 'Category: ';

            $category_string = join(", ", $category_array);
            $category_array = null;

            print $category_string;

            print '<b style="margin:0 0.5em">&middot;</b> Added:&nbsp;<a href="display.php?select=' . $select . '&browse[' . $paper['addition_date'] . ']=addition_date" class="navigation">' . date('M jS, Y', strtotime($paper['addition_date'])) . '</a>' . $today;

            print '<div class="noprint display-abstract"';

            print ($display != 'abstract') ? ' style="display:none"' : '';

            print '>';

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
                    print '<a href="' . htmlspecialchars($another_url) . '" target="_blank" class="anotherurl" title="' . htmlspecialchars(parse_url($another_url, PHP_URL_HOST)) . '">Link</a> <b style="margin:0 0.5em">&middot;</b> ';
                }
            }

            print '<a href="stable.php?id=' . $paper['id'] . '" target="_blank">Stable Link</a>';

            print '</div>';

            print '<div class="abstract display-abstract" style="';

            print ($display != 'abstract') ? 'display:none' : '';

            print'">' . $paper['abstract'] . '</div>';

            print '<div class="display-abstract" style="';

            print ($display != 'abstract') ? 'display:none' : '';

            print'">';

            if (!empty($paper['notes']))
                print '<div class="item-sticker ui-widget-content ui-corner-all" style="margin:6px;width:340px;float:left">
                        <div class="ui-widget-header items ui-corner-top" style="border:0"><b class="ui-dialog-titlebar">Notes</b></div><div class="separator" style="margin:0"></div>
                        <div class="alternating_row ui-corner-bottom" style="padding:4px 7px;max-height:200px;overflow:auto">' . $paper['notes'] . '&nbsp;
                        </div></div>';

            if (is_file(graphical_abstract($paper['file']))) {

                print '<div class="item-sticker ui-widget-content ui-corner-all" style="margin:6px;width:340px;float:left">
                        <div class="ui-widget-header items ui-corner-top" style="border:0"><b class="ui-dialog-titlebar">Graphical Abstract</b></div><div class="separator" style="margin:0"></div>
                        <div class="alternating_row ui-corner-bottom" style="padding:4px 7px;max-height:200px;overflow:auto">
                        <img src="' . htmlspecialchars('attachment.php?mode=inline&attachment=' . basename(graphical_abstract($paper['file']))) . '">
                        </div></div>';
            }

            print '</div></div></div>';
        }
    }
    if ($display == 'icons')
        print '</td></tr></table>';
}

/////////////read shelf/////////////////////////

function read_shelf($dbHandle) {

    if (isset($_SESSION['auth'])) {
        global $temp_dir;
        $cache_name = $temp_dir . DIRECTORY_SEPARATOR . 'lib_' . session_id() . DIRECTORY_SEPARATOR . 'shelf_files';
        $files_array = array();
        if (is_readable($cache_name)) {
            $content = file_get_contents($cache_name);
            $files_array = unserialize($content);
        } else {
            $user_query = $dbHandle->quote($_SESSION['user_id']);
            $result = $dbHandle->query("SELECT fileID FROM shelves WHERE userID=$user_query");
            $files_array = $result->fetchAll(PDO::FETCH_COLUMN);
            $result = null;
            file_put_contents($cache_name, serialize($files_array));
        }
        return $files_array;
    }
}

/////////////read desktop/////////////////////////

function read_desktop($dbHandle) {

    if (isset($_SESSION['auth'])) {
        global $temp_dir;
        $cache_name = $temp_dir . DIRECTORY_SEPARATOR . 'lib_' . session_id() . DIRECTORY_SEPARATOR . 'desk_files';
        $files_array = array();
        if (is_readable($cache_name)) {
            $content = file_get_contents($cache_name);
            $files_array = unserialize($content);
        } else {
            $id_query = $dbHandle->quote($_SESSION['user_id']);
            $query = $dbHandle->query("SELECT DISTINCT projects.projectID AS projectID,project FROM projects
                        LEFT OUTER JOIN projectsusers ON projects.projectID=projectsusers.projectID
                        WHERE (projects.userID=$id_query OR projectsusers.userID=$id_query) AND projects.active='1' ORDER BY project COLLATE NOCASE ASC");
            $files_array = $query->fetchAll(PDO::FETCH_ASSOC);
            $query = null;
            file_put_contents($cache_name, serialize($files_array));
        }
        return $files_array;
    }
}

/////////////update notes/////////////////////////

function update_notes($notesID, $fileID, $new_notes, $dbHandle) {

    if (!empty($notesID))
        $notesID = $dbHandle->quote($notesID);
    $userID = $dbHandle->quote($_SESSION['user_id']);
    $fileID = $dbHandle->quote($fileID);

    if (empty($notesID) && !empty($new_notes)) {
        $new_notes = $dbHandle->quote($new_notes);
        $dbHandle->exec("INSERT INTO notes (userID,fileID,notes) VALUES ($userID,$fileID,$new_notes)");
    } elseif (!empty($notesID)) {
        $dbHandle->beginTransaction();
        $dbHandle->exec("DELETE FROM notes WHERE notesID=$notesID");
        if (!empty($new_notes)) {
            $new_notes = $dbHandle->quote($new_notes);
            $dbHandle->exec("INSERT INTO notes (notesID,userID,fileID,notes) VALUES ($notesID,$userID,$fileID,$new_notes)");
        }
        $dbHandle->commit();
    }
}

#check nobody uses the record no shelfs no projects
#if no, delete record from table library, notes, attachments
#delete full text file and attachments

function delete_record($dbHandle, $files) {

    settype($files, "array");

    $dbHandle->beginTransaction();

    while (list(, $file) = each($files)) {
        $user = array();
        $file = intval($file);

        ##########	files	##########

        $result = $dbHandle->query("SELECT file FROM library WHERE id=$file");
        $filename = $result->fetchColumn();
        $result = null;

        if (is_file('library' . DIRECTORY_SEPARATOR . $filename))
            unlink('library' . DIRECTORY_SEPARATOR . $filename);

        $integer1 = sprintf("%05d", intval($filename));

        $supplementary_files = glob('library/supplement/' . $integer1 . '*', GLOB_NOSORT);
        if (is_array($supplementary_files)) {
            foreach ($supplementary_files as $supplementary_file) {
                @unlink($supplementary_file);
            }
        }
        $png_files = glob('library/pngs/' . $integer1 . '*.png', GLOB_NOSORT);
        if (is_array($png_files)) {
            foreach ($png_files as $png_file) {
                @unlink($png_file);
            }
        }
        ##########	library	##########

        $dbHandle->exec("DELETE FROM library WHERE id=$file");

        ##########	shelves	##########

        $dbHandle->exec("DELETE FROM shelves WHERE fileID=$file");

        ##########	categories	##########

        $dbHandle->exec("DELETE FROM filescategories WHERE fileID=$file");

        ##########	desktop	##########

        $dbHandle->exec("DELETE FROM projectsfiles WHERE fileID=$file");

        ##########	notes	##########

        $dbHandle->exec("DELETE FROM notes WHERE fileID=$file");

        ##########	PDF annotations ##########

        $dbHandle->exec("DELETE FROM yellowmarkers WHERE filename='$filename'");
        $dbHandle->exec("DELETE FROM annotations WHERE filename='$filename'");

        ##########	clipboard	##########

        if (!empty($_SESSION['session_clipboard'])) {
            $key = array_search($file, $_SESSION['session_clipboard']);
            unset($_SESSION['session_clipboard'][$key]);
        }
    }

    $dbHandle->commit();

    reset($files);

    ##########	attach full text	##########

    $fdatabase = dirname(__FILE__) . DIRECTORY_SEPARATOR . 'library' . DIRECTORY_SEPARATOR . 'database' . DIRECTORY_SEPARATOR . 'fulltext.sq3';
    $fdatabase_query = $dbHandle->quote($fdatabase);
    $dbHandle->exec("ATTACH DATABASE " . $fdatabase_query . " AS database2");

    ##########	attach discussion	##########

    $ddatabase = dirname(__FILE__) . DIRECTORY_SEPARATOR . 'library' . DIRECTORY_SEPARATOR . 'database' . DIRECTORY_SEPARATOR . 'discussions.sq3';
    if (file_exists($ddatabase)) {
        $ddatabase_query = $dbHandle->quote($ddatabase);
        $dbHandle->exec("ATTACH DATABASE " . $ddatabase_query . " AS database3");
    }

    ##########	attach PDF bookmarks	##########

    $hdatabase = dirname(__FILE__) . DIRECTORY_SEPARATOR . 'library' . DIRECTORY_SEPARATOR . 'database' . DIRECTORY_SEPARATOR . 'history.sq3';
    if (file_exists($hdatabase)) {
        $hdatabase_query = $dbHandle->quote($hdatabase);
        $dbHandle->exec("ATTACH DATABASE " . $hdatabase_query . " AS database4");
    }

    $dbHandle->beginTransaction();

    while (list(, $value) = each($files)) {

        $file_query = $dbHandle->quote($value);

        $dbHandle->exec("DELETE FROM database2.full_text WHERE fileID=$file_query");
        if (file_exists($ddatabase))
            $dbHandle->exec("DELETE FROM database3.discussion WHERE fileID=$file_query");
        if (file_exists($hdatabase))
            $dbHandle->exec("DELETE FROM database4.bookmarks WHERE file='$filename'");
    }

    $dbHandle->commit();

    $dbHandle->exec("DETACH DATABASE database2");
    if (file_exists($ddatabase))
        $dbHandle->exec("DETACH DATABASE database3");
    if (file_exists($hdatabase))
        $dbHandle->exec("DETACH DATABASE database4");

    $export_files = read_export_files(0);
    $export_files = array_diff($export_files, $files);
    $export_files = array_values($export_files);

    cache_clear();

    save_export_files($export_files);

    if (!empty($error))
        return $error;
}

function save_setting($dbHandle, $setting_name, $setting_value) {
    $dbHandle->beginTransaction();
    $stmt = $dbHandle->prepare("DELETE FROM settings WHERE userID=:userID AND setting_name=:setting_name");
    $stmt->bindParam(':userID', $userID, PDO::PARAM_STR);
    $stmt->bindParam(':setting_name', $setting_name, PDO::PARAM_STR);
    if (isset($_SESSION['user_id']))
        $userID = $_SESSION['user_id'];
    if (isset($_GET['userID']))
        $userID = $_GET['userID'];
    $stmt->execute();
    $stmt = null;
    if (!empty($setting_value)) {
        $stmt2 = $dbHandle->prepare("INSERT INTO settings (userID,setting_name,setting_value) VALUES (:userID,:setting_name,:setting_value)");
        $stmt2->bindParam(':userID', $userID, PDO::PARAM_STR);
        $stmt2->bindParam(':setting_name', $setting_name, PDO::PARAM_STR);
        $stmt2->bindParam(':setting_value', $setting_value, PDO::PARAM_STR);
        if (isset($_SESSION['user_id']))
            $userID = $_SESSION['user_id'];
        if (isset($_GET['userID']))
            $userID = $_GET['userID'];
        $stmt2->execute();
        $stmt2 = null;
    }
    $dbHandle->commit();
}

function get_setting($dbHandle, $setting_name) {
    $stmt = $dbHandle->prepare("SELECT setting_value FROM settings WHERE userID=:userID AND setting_name=:setting_name LIMIT 1");
    $stmt->bindParam(':userID', $userID, PDO::PARAM_STR);
    $stmt->bindParam(':setting_name', $setting_name, PDO::PARAM_STR);
    $userID = $_SESSION['user_id'];
    $stmt->execute();
    $setting_value = $stmt->fetchColumn();
    $stmt = null;
    return $setting_value;
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

function mobile_show_search_results($result, $display) {

    $i = 0;

    if ($display == 'icons') {
        print '<table id="icon-container">
        <tr><td>';
    } else {
        print '<div data-role="collapsible-set" data-inset="false">';
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
                    $first = trim($array2[1]);
                    $first = substr($array2[1], 3, -1);
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

            if (is_readable('../library/' . $paper['file']))
                print '<a href="' . htmlspecialchars('downloadpdf.php?file=' . urlencode($paper['file']) . '#pagemode=none&scrollbar=1&navpanes=0&toolbar=1&statusbar=0&page=1&view=FitH,0') . '" target="_blank" style="display:block;text-decoration:none">';

            print '<div class="thumb-items-top"><div class="thumb-titles"><div style="overflow:hidden;white-space:nowrap;font-weight:normal;font-size:0.8em">' . $paper['title'] . '<br>' . $first_author . $etal;
            if (!empty($paper['year']))
                print ' (' . substr($paper['year'], 0, 4) . ')';
            print '</div></div>';

            if (is_readable('../library/' . $paper['file'])) {

                print '</a><a href="' . htmlspecialchars('downloadpdf.php?file=' . urlencode($paper['file']) . '#pagemode=none&scrollbar=1&navpanes=0&toolbar=1&statusbar=0&page=1&view=FitH,0') . '" target="_blank" style="display:block">';
                print '<img src="icon.php?file=' . $paper['file'] . '" style="width:306px;border:0" alt="Loading PDF..."></a>';
            } else {
                print '<div style="text-align:center;margin-top:90px;font-size:18px;color:#b5b6b8">No PDF</div>';
            }

            print '</div>';

            print '<form><input class="update_clipboard" name="checkbox-clipboard" id="checkbox-clipboard-' . $paper['id'] . '" type="checkbox" data-mini="false"';

            if (isset($_SESSION['session_clipboard']) && in_array($paper['id'], $_SESSION['session_clipboard']))
                print ' checked="checked"';

            print '><label for="checkbox-clipboard-' . $paper['id'] . '"><span style="font-size:0.8em">Clipboard</span></label></form>';

            print PHP_EOL . '</div></div>';
        } else {

            print PHP_EOL . '<div data-role="collapsible">';

            print PHP_EOL . '<h4 class="accordeon" data-fileid="' . $paper['id'] . '" style="margin:0">' . $paper['title'] . '</h4>';

            print '<div style="padding:0 20px"></div></div>';
        }
    }
    if ($display == 'icons') {
        print '</td></tr></table>';
    } else {
        print '</div>';
    }
}

?>
