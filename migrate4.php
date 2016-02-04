<?php
//THIS SCRIPT UPGRADES I, LIBRARIAN DATABASES FROM 2.10 to 2.11 FORMAT
//ADD TERTIARY_TITLE COLUMN TO TABLE LIBRARY AND UPGRADE EDITORS
//ADD COLUMN ACTIVE TO PROJECTS
//ADD FILEHASH COLUMN TO TABLE LIBRARY
//CONSOLIDATE DISCUSSIONS INTO ONE DATABASE

ignore_user_abort();

include_once 'data.php';
include_once 'functions.php';

function migrate_authors($string) {
    $result = '';
    $array = array();
    $new_authors = array();
    $string = str_ireplace(' and ', ' , ', $string);
    $string = str_ireplace(', and ', ' , ', $string);
    $string = str_ireplace(',and ', ' , ', $string);
    $string = str_ireplace(';', ',', $string);
    $array = explode(',', $string);
    $array = array_filter($array);
    if (!empty($array)) {
        foreach ($array as $author) {
            $author = trim($author);
            $author = str_replace('"', '', $author);
            $space = strpos($author, ' ');
            if ($space === false) {
                $last = trim($author);
                $first = '';
            } else {
                $last = trim(substr($author, 0, $space));
                $first = trim(substr($author, $space + 1));
            }
            if (!empty($last))
                $new_authors[] = 'L:"' . $last . '",F:"' . $first . '"';
        }
        if (count($new_authors) > 0)
            $result = join(';', $new_authors);
    }
    return $result;
}

//ADD TERTIARY_TITLE COLUMN TO TABLE LIBRARY AND UPGRADE EDITORS
database_connect(IL_DATABASE_PATH, 'library');
$dbHandle->sqliteCreateFunction('migrateauthors', 'migrate_authors', 1);
$dbHandle->beginTransaction();
$dbHandle->exec("ALTER TABLE library ADD COLUMN tertiary_title TEXT NOT NULL DEFAULT ''");
$dbHandle->exec("ALTER TABLE library ADD COLUMN filehash TEXT NOT NULL DEFAULT ''");
$dbHandle->exec("ALTER TABLE projects ADD COLUMN active TEXT NOT NULL DEFAULT '1'");
$dbHandle->exec("UPDATE library SET editor=migrateauthors(editor) WHERE editor NOT LIKE '%L:\"%'");
$dbHandle->commit();
$dbHandle = null;

//CONSOLIDATE DISCUSSIONS INTO ONE DATABASE
database_connect(IL_DATABASE_PATH, 'filediscussion');
$dbHandle->beginTransaction();
$dbHandle->exec("CREATE TABLE IF NOT EXISTS discussion (id INTEGER PRIMARY KEY,"
        . " fileID INTEGER NOT NULL,"
        . " user TEXT NOT NULL DEFAULT '',"
        . " timestamp TEXT NOT NULL DEFAULT '',"
        . " message TEXT NOT NULL DEFAULT '')");
$dbHandle->exec("ALTER TABLE discussion RENAME TO filediscussion");
$dbHandle->exec("CREATE TABLE projectdiscussion (id integer PRIMARY KEY,"
        . " projectID integer NOT NULL,"
        . " user text NOT NULL DEFAULT '',"
        . " timestamp text NOT NULL DEFAULT '',"
        . " message text NOT NULL DEFAULT '')");
$dbHandle->commit();
$dbs = glob(IL_DATABASE_PATH . DIRECTORY_SEPARATOR . 'project*.sq3', GLOB_NOSORT);
if (is_array($dbs)) {
    foreach ($dbs as $db) {
        $projID = substr(basename($db, '.sq3'), 7);
        $database_query = $dbHandle->quote($db);
        $dbHandle->exec("ATTACH DATABASE " . $database_query . " AS db2");
        $dbHandle->beginTransaction();
        $result = $dbHandle->query("SELECT user,timestamp,message FROM db2.discussion");
        while ($row = $result->fetch(PDO::FETCH_NAMED)) {
            $projectID = intval($projID);
            $user = $dbHandle->quote($row['user']);
            $timestamp = $dbHandle->quote($row['timestamp']);
            $message = $dbHandle->quote($row['message']);
            $dbHandle->exec("INSERT INTO projectdiscussion (projectID,user,timestamp,message) VALUES ($projectID, $user, $timestamp, $message)");
            var_dump($dbHandle->errorInfo());
        }
        $dbHandle->commit();
        $dbHandle->exec("DETACH DATABASE db2");
        unlink($db);
    }
}

$dbHandle = null;
rename(IL_DATABASE_PATH . DIRECTORY_SEPARATOR . 'filediscussion.sq3', IL_DATABASE_PATH . DIRECTORY_SEPARATOR . 'discussions.sq3');
?>
<html>
    <body>
        <script type="text/javascript">
            top.location = '<?php print $url ?>';
        </script>
    </body>
</html>