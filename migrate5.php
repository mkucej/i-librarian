<?php

ignore_user_abort(true);

echo <<<EOT
    <script type="text/javascript">
        var div = parent.document.getElementById('first-loader').childNodes[1];
        div.innerHTML = div.innerHTML + '<p style="font-size: 26px;">Please wait, upgrading&hellip;</p>';
    </script>
EOT;

include_once 'data.php';
include_once 'functions.php';

// Install every non existing table and folder, to be sure.
include 'install.php';

// Upgrade library table.
$dbHandle = database_connect(IL_DATABASE_PATH, 'library');

// Set db version.
$dbHandle->exec("PRAGMA user_version = 36");

// Add new column.
$query = "ALTER TABLE library ADD COLUMN bibtex_type TEXT NOT NULL DEFAULT ''";
$stmt = $dbHandle->prepare($query);
$stmt->execute();
$stmt = null;

// Create new indexes.
$dbHandle->exec("CREATE INDEX IF NOT EXISTS title_ind ON library(title)");

$dbHandle->exec("CREATE INDEX IF NOT EXISTS year_ind ON library(year DESC)");

$dbHandle->exec("CREATE INDEX IF NOT EXISTS rating_ind ON library(rating DESC)");

$dbHandle->exec("CREATE INDEX IF NOT EXISTS added_by_ind ON library(added_by)");

$dbHandle->exec("CREATE INDEX IF NOT EXISTS doi_ind ON library(doi)");

$dbHandle->exec("CREATE UNIQUE INDEX IF NOT EXISTS file_ind ON library(file)");

$dbHandle->exec("CREATE UNIQUE INDEX IF NOT EXISTS notes_ind ON notes(userID, fileID)");

// Generate default citation keys.
$stmt = $dbHandle->prepare("UPDATE library SET bibtex=:bibtex WHERE id=:id");

$stmt->bindParam(':bibtex', $bibtex, PDO::PARAM_STR);
$stmt->bindParam(':id', $id, PDO::PARAM_STR);

$dbHandle->beginTransaction();

$result = $dbHandle->query("SELECT id, authors, year FROM library WHERE bibtex=''");

while ($row = $result->fetch(PDO::FETCH_ASSOC)) {

    extract($row);

    $bibtex_author = 'unknown';
    if (!empty($authors)) {
        $bibtex_author = substr($authors, 3);
        $bibtex_author = substr($bibtex_author, 0, strpos($bibtex_author, ',') - 1);
        $bibtex_author = str_replace(array(' ', '{', '}'), '', $bibtex_author);
        $bibtex_author = str_replace(' ', '', $bibtex_author);
    }

    $bibtex_year = '0000';
    $bibtex_year_array = explode('-', $year);
    if (!empty($bibtex_year_array[0]) && is_numeric($bibtex_year_array[0])) {
        $bibtex_year = $bibtex_year_array[0];
    }
    $bibtex = utf8_deaccent($bibtex_author) . '-' . $bibtex_year . '-ID' . $id;

    $stmt->execute();
}

$dbHandle->commit();

$stmt = null;
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
                rename(IL_LIBRARY_PATH . DIRECTORY_SEPARATOR . $oldname, IL_PDF_PATH . DIRECTORY_SEPARATOR . '01' . DIRECTORY_SEPARATOR . $oldname);
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

// Delete obsolete ilibrarian.ini.
if (is_writeable('ilibrarian.ini')) {
    @unlink('ilibrarian.ini');
}