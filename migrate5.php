<?php
// TODO: Generate default citation keys.
include_once 'data.php';
include_once 'functions.php';

// Upgrade library table.
$dbHandle = database_connect(IL_DATABASE_PATH, 'library');

$query = "ALTER TABLE library ADD COLUMN bibtex_type TEXT NOT NULL DEFAULT ''";
$stmt = $dbHandle->prepare($query);
$stmt->execute();
$stmt = null;

// Set db version.
$dbHandle->exec("PRAGMA user_version = 36");

$dbHandle = null;

// Upgrade settings.
$dbHandle = database_connect(IL_USER_DATABASE_PATH, 'users');

$query = "DELETE FROM settings WHERE setting_value=''";
$stmt = $dbHandle->prepare($query);
$stmt->execute();
$stmt = null;

$query = "UPDATE settings SET setting_name=substr(setting_name,8) WHERE substr(setting_name,1,7)='global_'";
$stmt = $dbHandle->prepare($query);
$stmt->execute();
$stmt = null;

$query = "UPDATE settings SET setting_name=substr(setting_name,10) WHERE substr(setting_name,1,9)='settings_'";
$stmt = $dbHandle->prepare($query);
$stmt->execute();
$stmt = null;

$dbHandle = null;

// Upgrade full_text table.
$dbHandle = database_connect(IL_DATABASE_PATH, 'fulltext');

$dbHandle->exec("PRAGMA journal_mode = DELETE");

$query = "DELETE FROM full_text WHERE full_text='' OR fileID=0";
$stmt = $dbHandle->prepare($query);
$stmt->execute();
$stmt = null;

$query = "CREATE TABLE IF NOT EXISTS full_text2 (
    fileID integer PRIMARY KEY,
    full_text text NOT NULL DEFAULT ''
    )";
$stmt = $dbHandle->prepare($query);
$stmt->execute();
$stmt = null;

$query = "INSERT OR IGNORE INTO full_text2 (fileID, full_text) SELECT fileID, full_text FROM full_text";
$stmt = $dbHandle->prepare($query);
$stmt->execute();
$stmt = null;

$query = "DROP TABLE IF EXISTS full_text";
$stmt = $dbHandle->prepare($query);
$stmt->execute();
$stmt = null;

$query = "ALTER TABLE full_text2 RENAME TO full_text";
$stmt = $dbHandle->prepare($query);
$stmt->execute();
$stmt = null;

$query = "CREATE TRIGGER trigger_fulltext_delete AFTER DELETE ON full_text
        BEGIN
        UPDATE fulltext_log SET ch_time = strftime('%s', 'now') WHERE ch_table = 'full_text';
        END;";
$stmt = $dbHandle->prepare($query);
$stmt->execute();
$stmt = null;

$query = "CREATE TRIGGER trigger_fulltext_insert AFTER INSERT ON full_text
        BEGIN
        UPDATE fulltext_log SET ch_time = strftime('%s', 'now') WHERE ch_table = 'full_text';
        END;";
$stmt = $dbHandle->prepare($query);
$stmt->execute();
$stmt = null;

$query = "CREATE TRIGGER trigger_fulltext_update AFTER UPDATE ON full_text
        BEGIN
        UPDATE fulltext_log SET ch_time = strftime('%s', 'now') WHERE ch_table = 'full_text';
        END;";
$stmt = $dbHandle->prepare($query);
$stmt->execute();
$stmt = null;

$dbHandle = null;

// Make new dirs and copy contents correctly.
if (!is_dir(IL_SUPPLEMENT_PATH . DIRECTORY_SEPARATOR . '01')) {
    @mkdir(IL_SUPPLEMENT_PATH . DIRECTORY_SEPARATOR . '01', 0755, true);
}
if (!is_dir(IL_PDF_PATH)) {
    @mkdir(IL_PDF_PATH, 0755, true);
}
if (!is_dir(IL_PDF_PATH . DIRECTORY_SEPARATOR . '01')) {
    @mkdir(IL_PDF_PATH . DIRECTORY_SEPARATOR . '01', 0755, true);
}
if (!is_dir(IL_PDF_CACHE_PATH)) {
    @mkdir(IL_PDF_CACHE_PATH, 0755, true);
}

// Separate files into subfolders.
$errors = array();
$dir = new DirectoryIterator(IL_LIBRARY_PATH);
foreach ($dir as $file) {
    if ($file->isFile()) {
        $oldname = $file->getFilename();
        if (pathinfo($oldname, PATHINFO_EXTENSION) === 'pdf') {
            $int = intval(substr(basename($oldname), 0, 5));
            if ($int >= 1 && $int < 100000) {
                rename(IL_LIBRARY_PATH . DIRECTORY_SEPARATOR . $oldname, IL_PDF_CACHE_PATH . DIRECTORY_SEPARATOR . '01' . DIRECTORY_SEPARATOR . $oldname);
            }
        }
    }
}

$dir = new DirectoryIterator(IL_SUPPLEMENT_PATH);
foreach ($dir as $file) {
    if ($file->isFile()) {
        $oldname = $file->getFilename();
        $int = intval(substr(basename($oldname), 0, 5));
        if ($int >= 1 && $int < 100000) {
            rename(IL_SUPPLEMENT_PATH . DIRECTORY_SEPARATOR . $oldname, IL_SUPPLEMENT_PATH . DIRECTORY_SEPARATOR . '01' . DIRECTORY_SEPARATOR . $oldname);
        }
    }
}