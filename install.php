<?php

// Create library dirs.
if (!is_dir(IL_DATABASE_PATH)) {
    @mkdir(IL_DATABASE_PATH, 0755);
}
if (!is_dir(IL_USER_DATABASE_PATH)) {
    @mkdir(IL_USER_DATABASE_PATH, 0755);
}
if (!is_dir(IL_SUPPLEMENT_PATH . DIRECTORY_SEPARATOR . '0' . DIRECTORY_SEPARATOR . '0')) {
    @mkdir(IL_SUPPLEMENT_PATH . DIRECTORY_SEPARATOR . '0' . DIRECTORY_SEPARATOR . '0', 0755, true);
}
if (!is_dir(IL_IMAGE_PATH)) {
    @mkdir(IL_IMAGE_PATH, 0755, true);
}
if (!is_dir(IL_PDF_PATH . DIRECTORY_SEPARATOR . '0' . DIRECTORY_SEPARATOR . '0')) {
    @mkdir(IL_PDF_PATH . DIRECTORY_SEPARATOR . '0' . DIRECTORY_SEPARATOR . '0', 0755, true);
}
if (!is_dir(IL_PDF_CACHE_PATH)) {
    @mkdir(IL_PDF_CACHE_PATH, 0755, true);
}

// Create database files.
$dbHandle = database_connect(IL_DATABASE_PATH, 'library');


// Set journal mode to wal, or delete in old versions.
$dbHandle->exec('PRAGMA journal_mode = DELETE');
$dbHandle->exec('PRAGMA journal_mode = WAL');

// Set db version.
$dbHandle->exec("PRAGMA user_version = 44");

// Create library tables.
$dbHandle->exec("CREATE TABLE IF NOT EXISTS library (
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
                filehash text NOT NULL DEFAULT '',
                bibtex_type text NOT NULL DEFAULT ''
                )");

$dbHandle->exec("CREATE TABLE IF NOT EXISTS shelves (
                fileID integer NOT NULL DEFAULT '',
                userID integer NOT NULL DEFAULT '',
                UNIQUE (fileID, userID)
                )");

$dbHandle->exec("CREATE TABLE IF NOT EXISTS categories (
                categoryID integer PRIMARY KEY,
                category text NOT NULL DEFAULT ''
                )");

$dbHandle->exec("CREATE TABLE IF NOT EXISTS filescategories (
                fileID integer NOT NULL,
                categoryID integer NOT NULL,
                UNIQUE(fileID, categoryID)
		  )");

$dbHandle->exec("CREATE TABLE IF NOT EXISTS projects (
                projectID integer PRIMARY KEY,
                userID integer NOT NULL,
                project text NOT NULL,
                active text NOT NULL
                )");

$dbHandle->exec("CREATE TABLE IF NOT EXISTS projectsfiles (
                projectID integer NOT NULL,
                fileID integer NOT NULL,
                UNIQUE (projectID, fileID)
                )");

$dbHandle->exec("CREATE TABLE IF NOT EXISTS projectsusers (
                projectID integer NOT NULL,
                userID integer NOT NULL,
                UNIQUE (projectID, userID)
                )");

$dbHandle->exec("CREATE TABLE IF NOT EXISTS notes (
                notesID integer PRIMARY KEY,
                userID integer NOT NULL,
                fileID integer NOT NULL,
                notes text NOT NULL DEFAULT ''
                )");

$dbHandle->exec("CREATE TABLE IF NOT EXISTS searches (
                searchID integer PRIMARY KEY,
                userID integer NOT NULL,
                searchname text NOT NULL DEFAULT '',
                searchfield text NOT NULL DEFAULT '',
                searchvalue text NOT NULL DEFAULT ''
                )");

$dbHandle->exec("CREATE TABLE IF NOT EXISTS yellowmarkers (
                id INTEGER PRIMARY KEY,
                userID INTEGER NOT NULL,
                filename TEXT NOT NULL,
                page INTEGER NOT NULL,
                top TEXT NOT NULL,
                left TEXT NOT NULL,
                width TEXT NOT NULL,
                UNIQUE (userID, filename, page, top, left)
                )");

$dbHandle->exec("CREATE TABLE IF NOT EXISTS annotations (
                id INTEGER PRIMARY KEY,
                userID INTEGER NOT NULL,
                filename TEXT NOT NULL,
                page INTEGER NOT NULL,
                top TEXT NOT NULL,
                left TEXT NOT NULL,
                annotation TEXT NOT NULL,
                UNIQUE (userID, filename, page, top, left)
                )");

$dbHandle->exec("CREATE TABLE IF NOT EXISTS flagged ("
        . "id INTEGER PRIMARY KEY,"
        . " userID INTEGER NOT NULL,"
        . " database TEXT NOT NULL,"
        . " uid TEXT NOT NULL,"
        . " UNIQUE (userID,database,uid))");


$dbHandle->exec("CREATE TABLE IF NOT EXISTS library_log (
                id integer PRIMARY KEY,
                ch_table text NOT NULL DEFAULT '',
                ch_time text NOT NULL DEFAULT ''
                )");

// Create library indexes.
$dbHandle->exec("CREATE INDEX IF NOT EXISTS journal_ind ON library (journal)");

$dbHandle->exec("CREATE INDEX IF NOT EXISTS secondary_title_ind ON library (secondary_title)");

$dbHandle->exec("CREATE INDEX IF NOT EXISTS addition_date_ind ON library (addition_date)");

$dbHandle->exec("CREATE INDEX IF NOT EXISTS title_ind ON library(title)");

$dbHandle->exec("CREATE INDEX IF NOT EXISTS year_ind ON library(year DESC)");

$dbHandle->exec("CREATE INDEX IF NOT EXISTS rating_ind ON library(rating DESC)");

$dbHandle->exec("CREATE INDEX IF NOT EXISTS added_by_ind ON library(added_by)");

$dbHandle->exec("CREATE INDEX IF NOT EXISTS doi_ind ON library(doi)");

$dbHandle->exec("CREATE UNIQUE INDEX IF NOT EXISTS file_ind ON library(file)");

$dbHandle->exec("CREATE UNIQUE INDEX IF NOT EXISTS notes_ind ON notes(userID, fileID)");

// Create triggers.
$tables = array('annotations', 'categories', 'filescategories', 'flagged', 'library', 'notes',
    'projects', 'projectsfiles', 'projectsusers', 'searches', 'shelves', 'yellowmarkers');

foreach ($tables as $table) {
    $dbHandle->exec("INSERT INTO library_log (ch_table, ch_time)
                            VALUES('" . $table . "', strftime('%s', 'now'))");

    $dbHandle->exec("CREATE TRIGGER IF NOT EXISTS trigger_" . $table . "_delete AFTER DELETE ON " . $table . "
                            BEGIN
                                UPDATE library_log SET ch_time = strftime('%s', 'now') WHERE ch_table = '" . $table . "';
                            END;");

    $dbHandle->exec("CREATE TRIGGER IF NOT EXISTS trigger_" . $table . "_insert AFTER INSERT ON " . $table . "
                            BEGIN
                                UPDATE library_log SET ch_time = strftime('%s', 'now') WHERE ch_table = '" . $table . "';
                            END;");

    $dbHandle->exec("CREATE TRIGGER IF NOT EXISTS trigger_" . $table . "_update AFTER UPDATE ON " . $table . "
                            BEGIN
                                UPDATE library_log SET ch_time = strftime('%s', 'now') WHERE ch_table = '" . $table . "';
                            END;");
}

$dbHandle = null;

$dbHandle = database_connect(IL_DATABASE_PATH, 'fulltext');

$dbHandle->exec('PRAGMA journal_mode = DELETE');
$dbHandle->exec('PRAGMA journal_mode = WAL');

// Create fulltext tables.
$dbHandle->exec("CREATE TABLE IF NOT EXISTS full_text (
                    fileID integer PRIMARY KEY,
                    full_text text NOT NULL DEFAULT ''
                    )");

$dbHandle->exec("CREATE TABLE IF NOT EXISTS fulltext_log (
                id integer PRIMARY KEY,
                ch_table text NOT NULL DEFAULT '',
                ch_time text NOT NULL DEFAULT ''
                )");

// Create triggers.
$dbHandle->exec("INSERT INTO fulltext_log (ch_table, ch_time)
                        VALUES('full_text', strftime('%s', 'now'))");

$dbHandle->exec("CREATE TRIGGER IF NOT EXISTS trigger_fulltext_delete AFTER DELETE ON full_text
                        BEGIN
                            UPDATE fulltext_log SET ch_time = strftime('%s', 'now') WHERE ch_table = 'full_text';
                        END;");

$dbHandle->exec("CREATE TRIGGER IF NOT EXISTS trigger_fulltext_insert AFTER INSERT ON full_text
                        BEGIN
                            UPDATE fulltext_log SET ch_time = strftime('%s', 'now') WHERE ch_table = 'full_text';
                        END;");

$dbHandle->exec("CREATE TRIGGER IF NOT EXISTS trigger_fulltext_update AFTER UPDATE ON full_text
                        BEGIN
                            UPDATE fulltext_log SET ch_time = strftime('%s', 'now') WHERE ch_table = 'full_text';
                        END;");

$dbHandle = null;

// Create user tables.
$dbHandle = database_connect(IL_USER_DATABASE_PATH, 'users');

$dbHandle->exec('PRAGMA journal_mode = DELETE');
$dbHandle->exec('PRAGMA journal_mode = WAL');

$dbHandle->exec("CREATE TABLE IF NOT EXISTS users (
                userID integer PRIMARY KEY,
                username text UNIQUE NOT NULL DEFAULT '',
                password text NOT NULL DEFAULT '',
                permissions text NOT NULL DEFAULT 'U'
                )");

$dbHandle->exec("CREATE TABLE IF NOT EXISTS settings (
                userID integer NOT NULL DEFAULT '',
                setting_name text NOT NULL DEFAULT '',
                setting_value text NOT NULL DEFAULT ''
                )");

$dbHandle->exec("CREATE TABLE IF NOT EXISTS logins (
                id INTEGER PRIMARY KEY,
                userID INTEGER NOT NULL DEFAULT '',
                sessionID TEXT NOT NULL DEFAULT '',
                logintime TEXT NOT NULL DEFAULT ''
                )");

$dbHandle = null;

// Create discussion tables.
database_connect(IL_DATABASE_PATH, 'discussions');

$dbHandle->exec('PRAGMA journal_mode = DELETE');
$dbHandle->exec('PRAGMA journal_mode = WAL');

$dbHandle->exec("CREATE TABLE IF NOT EXISTS projectdiscussion ("
        . "id INTEGER PRIMARY KEY,"
        . " projectID integer NOT NULL,"
        . " user TEXT NOT NULL,"
        . " timestamp TEXT NOT NULL,"
        . " message TEXT NOT NULL)");

    $dbHandle->exec("CREATE TABLE IF NOT EXISTS filediscussion ("
            . "id INTEGER PRIMARY KEY,"
            . " fileID INTEGER NOT NULL,"
            . " user TEXT NOT NULL,"
            . " timestamp TEXT NOT NULL,"
            . " message TEXT NOT NULL)");

$dbHandle = null;

// Create history tables.
database_connect(IL_DATABASE_PATH, 'history');

$dbHandle->exec('PRAGMA journal_mode = DELETE');
$dbHandle->exec('PRAGMA journal_mode = WAL');

$dbHandle->exec("CREATE TABLE IF NOT EXISTS search_tables ("
            . "id INTEGER PRIMARY KEY,"
            . "table_name TEXT NOT NULL DEFAULT '',"
            . "created TEXT NOT NULL DEFAULT '',"
            . "total_rows TEXT NOT NULL DEFAULT '')");

$dbHandle->exec("CREATE TABLE IF NOT EXISTS usersfiles (
                id INTEGER PRIMARY KEY,
                userID INTEGER NOT NULL DEFAULT '',
                fileID INTEGER NOT NULL DEFAULT '',
                viewed INTEGER NOT NULL DEFAULT '',
                UNIQUE(userID,fileID)
                )");

$dbHandle->exec("CREATE TABLE IF NOT EXISTS bookmarks (
                id INTEGER PRIMARY KEY,
                userID INTEGER NOT NULL DEFAULT '',
                file TEXT NOT NULL DEFAULT '',
                page INTEGER NOT NULL DEFAULT 1,
                UNIQUE(userID,file)
                )");

$dbHandle = null;



