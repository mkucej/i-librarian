<?php
//THIS SCRIPT UPGRADES I, LIBRARIAN DATABASES FROM 2.7 to 2.8 FORMAT
//ADD TABLE LOG INTO LIBRARY AND FULLTEXT PLUS TRIGGERS

ignore_user_abort(true);

echo <<<EOT
    <script type="text/javascript">
        var div = parent.document.getElementById('first-loader').childNodes[1];
        div.innerHTML = div.innerHTML + '<p style="font-size: 26px;">Please wait, upgrading&hellip;</p>';
    </script>
EOT;

include_once 'data.php';
include_once 'functions.php';

database_connect(IL_DATABASE_PATH, 'library');
$dbHandle->beginTransaction();
$dbHandle->exec("UPDATE library SET reference_type='article'");
$dbHandle->exec("CREATE TABLE library_log (
                id integer PRIMARY KEY,
                ch_table text NOT NULL DEFAULT '',
                ch_time text NOT NULL DEFAULT ''
                )");
$tables = array('annotations','categories','filescategories','flagged','library','notes',
        'projects','projectsfiles','projectsusers','searches','shelves','yellowmarkers');
foreach ($tables as $table) {
    $dbHandle->exec("INSERT INTO library_log (ch_table,ch_time)
                    VALUES('".$table."',strftime('%s','now'))");
    $dbHandle->exec("CREATE TRIGGER trigger_".$table."_delete AFTER DELETE ON ".$table."
                    BEGIN
                        UPDATE library_log SET ch_time=strftime('%s','now') WHERE ch_table='".$table."';
                    END;");
    $dbHandle->exec("CREATE TRIGGER trigger_".$table."_insert AFTER INSERT ON ".$table."
                    BEGIN
                        UPDATE library_log SET ch_time=strftime('%s','now') WHERE ch_table='".$table."';
                    END;");
    $dbHandle->exec("CREATE TRIGGER trigger_".$table."_update AFTER UPDATE ON ".$table."
                    BEGIN
                        UPDATE library_log SET ch_time=strftime('%s','now') WHERE ch_table='".$table."';
                    END;");
}
$dbHandle->commit();
$dbHandle = null;

database_connect(IL_DATABASE_PATH, 'fulltext');
$dbHandle->beginTransaction();
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
?>
