<?php
//THIS SCRIPT UPGRADES I, LIBRARIAN DATABASES FROM 2.1 to 2.4 FORMAT
//ADD BIBTEX COLUMN TO TABLE LIBRARY AND SOME INDECES, UPGRADE AUTHORS

ignore_user_abort(true);

echo <<<EOT
    <script type="text/javascript">
        var div = parent.document.getElementById('first-loader').childNodes[1];
        div.innerHTML = div.innerHTML + '<p style="font-size: 26px;">Please wait, upgrading&hellip;</p>';
    </script>
EOT;

include_once 'data.php';
include_once 'functions.php';

function migrate_authors ($string) {
    $result = '';
    $array = array ();
    $new_authors = array ();
    $string = str_ireplace(' and ', ' , ', $string);
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
                $first = trim(substr($author, $space+1));
            }
            if (!empty($last)) $new_authors[] = 'L:"'.$last.'",F:"'.$first.'"';
        }
        if(count($new_authors) > 0) $result = join(';', $new_authors);
    }
    return $result;
}

database_connect(IL_DATABASE_PATH, 'library');
$dbHandle->sqliteCreateFunction('migrateauthors', 'migrate_authors', 1);
$dbHandle->beginTransaction();
$dbHandle->exec("ALTER TABLE library ADD COLUMN bibtex TEXT NOT NULL DEFAULT ''");
$dbHandle->exec("UPDATE library SET authors=migrateauthors(authors), authors_ascii=migrateauthors(authors_ascii) WHERE authors NOT LIKE '%L:\"%'");
$dbHandle->exec("CREATE INDEX journal_ind ON library (journal)");
$dbHandle->exec("CREATE INDEX secondary_title_ind ON library (secondary_title)");
$dbHandle->exec("CREATE INDEX addition_date_ind ON library (addition_date)");
$dbHandle->commit();
$dbHandle = null;
?>
